/* Carlos Confort - interacción del sitio (sin dependencias) */
(function () {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const meta = (name) => (document.querySelector(`meta[name="${name}"]`) || {}).content || '';
  const CSRF = meta('csrf-token');
  const API_CARRITO = meta('api-carrito');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function postForm(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      body: data,
      headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'fetch', Accept: 'application/json' },
      credentials: 'same-origin',
    });
    let json = null;
    try { json = await res.json(); } catch (e) { /* respuesta no JSON */ }
    if (!json) throw new Error('Respuesta inválida');
    return json;
  }

  /* ---------- Capa oscura compartida ---------- */
  const overlay = $('[data-overlay]');
  let cerrarActivo = null;
  function abrirCapa(cerrar) {
    cerrarActivo = cerrar;
    if (overlay) overlay.hidden = false;
    document.body.classList.add('sin-scroll');
  }
  function cerrarCapa() {
    if (overlay) overlay.hidden = true;
    document.body.classList.remove('sin-scroll');
    cerrarActivo = null;
  }
  if (overlay) overlay.addEventListener('click', () => cerrarActivo && cerrarActivo());
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (cerrarActivo) cerrarActivo();
      cerrarMenu();
    }
  });

  /* ---------- Menú de categorías ---------- */
  const menu = $('[data-menu]');
  const togglesMenu = $$('[data-menu-toggle]');
  function abrirMenu(btn) {
    if (!menu) return;
    menu.hidden = false;
    togglesMenu.forEach((b) => b.setAttribute('aria-expanded', 'true'));
    if (window.matchMedia('(max-width: 860px)').matches) document.body.classList.add('sin-scroll');
    const primero = $('a, button', menu);
    if (btn && btn.classList.contains('header__burger') && primero) primero.focus();
  }
  function cerrarMenu() {
    if (!menu || menu.hidden) return;
    menu.hidden = true;
    togglesMenu.forEach((b) => b.setAttribute('aria-expanded', 'false'));
    if (!cerrarActivo) document.body.classList.remove('sin-scroll');
  }
  togglesMenu.forEach((btn) => btn.addEventListener('click', (e) => {
    e.stopPropagation();
    menu && menu.hidden ? abrirMenu(btn) : cerrarMenu();
  }));
  $$('[data-menu-close]').forEach((b) => b.addEventListener('click', cerrarMenu));
  document.addEventListener('click', (e) => {
    if (menu && !menu.hidden && !menu.contains(e.target)) cerrarMenu();
  });

  /* ---------- Mini carrito ---------- */
  const drawer = $('[data-drawer]');
  const badge = $('[data-cart-count]');
  let ultimoFoco = null;

  function pintarCarrito(c, nuevoId) {
    if (badge) {
      badge.textContent = c.cantidad;
      badge.hidden = c.cantidad === 0;
      if (nuevoId && !reduceMotion) {
        badge.classList.remove('is-pop');
        void badge.offsetWidth;
        badge.classList.add('is-pop');
      }
    }
    if (!drawer) return;
    const body = $('[data-drawer-items]', drawer);
    const foot = $('[data-drawer-foot]', drawer);
    if (!c.items.length) {
      body.innerHTML = '<p class="drawer__vacio">Tu carrito está vacío.</p>';
      foot.hidden = true;
      return;
    }
    foot.hidden = false;
    body.innerHTML = c.items.map((it) => `
      <div class="drawer__item${it.id === nuevoId ? ' is-nuevo' : ''}">
        <img src="${esc(it.imagen)}" alt="" width="64" height="64">
        <div>
          <a href="${esc(it.url)}">${esc(it.nombre)}</a>
          <p>${it.cantidad} × ${esc(it.precio)}</p>
        </div>
      </div>`).join('');
    $('[data-drawer-subtotal]', drawer).textContent = c.subtotal;
    $('[data-drawer-transf]', drawer).textContent = c.total_transferencia;
  }

  function abrirDrawer(mensaje, esError) {
    if (!drawer) return;
    cerrarMenu();
    ultimoFoco = document.activeElement;
    const msg = $('[data-drawer-msg]', drawer);
    msg.textContent = mensaje || '';
    msg.classList.toggle('is-error', !!esError);
    drawer.hidden = false;
    requestAnimationFrame(() => drawer.classList.add('is-open'));
    abrirCapa(cerrarDrawer);
    const cerrar = $('[data-drawer-close]', drawer);
    if (cerrar) cerrar.focus();
  }
  function cerrarDrawer() {
    if (!drawer || drawer.hidden) return;
    drawer.classList.remove('is-open');
    const ocultar = () => { drawer.hidden = true; };
    reduceMotion ? ocultar() : setTimeout(ocultar, 250);
    cerrarCapa();
    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
  }
  $$('[data-drawer-close]').forEach((b) => b.addEventListener('click', cerrarDrawer));

  // Botón del carrito en el encabezado: abre el mini carrito (sin JS va a la página).
  $$('[data-cart-open]').forEach((a) => a.addEventListener('click', async (e) => {
    if (!drawer || !API_CARRITO) return;
    e.preventDefault();
    try {
      const res = await fetch(API_CARRITO, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const json = await res.json();
      pintarCarrito(json.carrito);
      abrirDrawer('');
    } catch (err) {
      window.location.href = a.href;
    }
  }));

  // Agregar al carrito
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-add-to-cart]');
    if (!form || !API_CARRITO) return;
    e.preventDefault();
    const btn = $('button[type="submit"]', form);
    if (btn) btn.classList.add('is-cargando');
    try {
      const json = await postForm(API_CARRITO, new FormData(form));
      pintarCarrito(json.carrito, json.agregado);
      abrirDrawer(json.mensaje, !json.ok);
    } catch (err) {
      form.submit(); // si falla la API, vamos por el camino sin JS
    } finally {
      if (btn) btn.classList.remove('is-cargando');
    }
  });

  /* ---------- Contadores de cantidad ---------- */
  $$('[data-stepper]').forEach((box) => {
    const input = $('input[type="number"]', box);
    if (!input) return;
    const autoSubmit = box.hasAttribute('data-autosubmit-stepper');
    let timer = null;
    const enviar = () => {
      if (!autoSubmit) return;
      clearTimeout(timer);
      timer = setTimeout(() => box.requestSubmit ? box.requestSubmit() : box.submit(), 450);
    };
    $$('[data-step]', box).forEach((b) => b.addEventListener('click', () => {
      const min = parseInt(input.min || '1', 10);
      const max = parseInt(input.max || '99', 10);
      const val = Math.min(max, Math.max(min, (parseInt(input.value, 10) || min) + parseInt(b.dataset.step, 10)));
      if (String(val) !== input.value) {
        input.value = val;
        enviar();
      }
    }));
    input.addEventListener('change', enviar);
  });

  /* ---------- Orden del listado ---------- */
  $$('select[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

  /* ---------- Filtros en móvil ---------- */
  const filtros = $('[data-filtros]');
  const abrirFiltrosBtn = $('[data-filtros-open]');
  function cerrarFiltros() {
    if (!filtros) return;
    filtros.classList.remove('is-open');
    if (abrirFiltrosBtn) { abrirFiltrosBtn.setAttribute('aria-expanded', 'false'); abrirFiltrosBtn.focus(); }
    cerrarCapa();
  }
  if (filtros && abrirFiltrosBtn) {
    abrirFiltrosBtn.addEventListener('click', () => {
      filtros.classList.add('is-open');
      abrirFiltrosBtn.setAttribute('aria-expanded', 'true');
      abrirCapa(cerrarFiltros);
      const cerrar = $('[data-filtros-close]', filtros);
      if (cerrar) cerrar.focus();
    });
    $$('[data-filtros-close]', filtros).forEach((b) => b.addEventListener('click', cerrarFiltros));
  }

  /* ---------- Carrusel de la home ---------- */
  const slider = $('[data-slider]');
  if (slider) {
    const slides = $$('[data-slide]', slider);
    const dots = $$('[data-slide-dot]', slider);
    let actual = 0;
    let timer = null;
    const tema = (i) => (slides[i].className.match(/slide--(\w+)/) || [])[1];
    const mostrar = (i) => {
      actual = (i + slides.length) % slides.length;
      slides.forEach((s, n) => {
        s.hidden = n !== actual;
        s.classList.toggle('is-entrando', n === actual && !reduceMotion);
      });
      dots.forEach((d, n) => d.setAttribute('aria-current', n === actual ? 'true' : 'false'));
      slider.dataset.tema = tema(actual) || '';
    };
    const iniciar = () => {
      if (reduceMotion || slides.length < 2) return;
      detener();
      timer = setInterval(() => mostrar(actual + 1), 6500);
    };
    const detener = () => { if (timer) clearInterval(timer); timer = null; };
    slider.dataset.tema = tema(0) || '';
    const prev = $('[data-slide-prev]', slider);
    const next = $('[data-slide-next]', slider);
    if (prev) prev.addEventListener('click', () => { mostrar(actual - 1); iniciar(); });
    if (next) next.addEventListener('click', () => { mostrar(actual + 1); iniciar(); });
    dots.forEach((d) => d.addEventListener('click', () => { mostrar(parseInt(d.dataset.slideDot, 10)); iniciar(); }));
    slider.addEventListener('mouseenter', detener);
    slider.addEventListener('mouseleave', iniciar);
    slider.addEventListener('focusin', detener);
    slider.addEventListener('focusout', iniciar);
    iniciar();
  }

  /* ---------- Galería de la ficha ---------- */
  const galeria = $('[data-galeria]');
  if (galeria) {
    const principal = $('[data-galeria-img]', galeria);
    $$('[data-galeria-thumb]', galeria).forEach((b, i, todos) => b.addEventListener('click', () => {
      principal.src = b.dataset.galeriaThumb;
      todos.forEach((t) => t.setAttribute('aria-current', t === b ? 'true' : 'false'));
    }));
  }

  /* ---------- Cálculo de envío en la ficha ---------- */
  $$('[data-envio]').forEach((form) => {
    const out = $('[data-envio-resultado]', form);
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const params = new URLSearchParams(new FormData(form));
      out.classList.remove('is-ok', 'is-error');
      out.textContent = 'Calculando…';
      try {
        const res = await fetch(form.action + '?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const json = await res.json();
        out.textContent = json.mensaje;
        out.classList.add(json.ok ? 'is-ok' : 'is-error');
      } catch (err) {
        out.textContent = 'No pudimos calcular el envío. Probá de nuevo.';
        out.classList.add('is-error');
      }
    });
  });

  /* ---------- Newsletter ---------- */
  $$('[data-newsletter]').forEach((form) => {
    const out = $('[data-newsletter-msg]', form);
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      out.classList.remove('is-ok', 'is-error');
      try {
        const json = await postForm(form.action, new FormData(form));
        out.textContent = json.mensaje;
        out.classList.add(json.ok ? 'is-ok' : 'is-error');
        if (json.ok) form.reset();
      } catch (err) {
        out.textContent = 'No pudimos guardar tu email. Probá de nuevo.';
        out.classList.add('is-error');
      }
    });
  });

  /* ---------- Finalizar pedido: envío o retiro ---------- */
  const checkout = $('[data-checkout]');
  if (checkout) {
    const datosEnvio = $('[data-datos-envio]');
    const filaEnvio = $('[data-fila-envio]');
    const filaRetiro = $('[data-fila-retiro]');
    const total = $('[data-total]');
    const actualizarEntrega = () => {
      const elegida = $('input[name="entrega"]:checked', checkout);
      const retiro = elegida && elegida.value === 'retiro';
      if (datosEnvio) datosEnvio.hidden = retiro;
      if (filaEnvio) filaEnvio.hidden = retiro;
      if (filaRetiro) filaRetiro.hidden = !retiro;
      if (total) total.textContent = retiro ? total.dataset.sinEnvio : total.dataset.conEnvio;
    };
    $$('[data-entrega]', checkout).forEach((r) => r.addEventListener('change', actualizarEntrega));
    checkout.addEventListener('submit', () => {
      const btn = $('button[type="submit"]', checkout);
      if (btn) { btn.classList.add('is-cargando'); btn.textContent = 'Registrando…'; }
    });
  }

  /* ---------- Aviso de cookies ---------- */
  const cookies = $('[data-cookies]');
  if (cookies) {
    $('[data-cookies-ok]', cookies).addEventListener('click', () => {
      const base = (meta('api-base') || '/api/').replace(/api\/$/, '') || '/';
      document.cookie = 'cc_aviso=1; max-age=31536000; path=' + base + '; SameSite=Lax';
      cookies.hidden = true;
    });
  }
})();
