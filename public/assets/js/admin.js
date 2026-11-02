/* Panel de administración - comportamiento (sin dependencias) */
(function () {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  // Menú lateral en celular
  const lateral = $('[data-lateral]');
  const toggle = $('[data-lateral-toggle]');
  if (lateral && toggle) {
    toggle.addEventListener('click', () => {
      const abierto = lateral.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && lateral.classList.contains('is-open')) {
        lateral.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  }

  // Confirmación antes de borrar
  $$('form[data-confirmar]').forEach((f) => f.addEventListener('submit', (e) => {
    if (!window.confirm(f.dataset.confirmar)) e.preventDefault();
  }));

  // Casilla que muestra u oculta un bloque (data-mostrar="#id")
  $$('input[type="checkbox"][data-mostrar]').forEach((c) => {
    const destino = $(c.dataset.mostrar);
    if (!destino) return;
    const actualizar = () => { destino.hidden = !c.checked; };
    c.addEventListener('change', actualizar);
    actualizar();
  });

  // Selects que filtran solos
  $$('select[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

  // Slug: sugerencia automática mientras está vacío o sin tocar
  const slugify = (t) => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/ñ/g, 'n').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  $$('[data-slug-desde]').forEach((slug) => {
    const origen = document.getElementById(slug.dataset.slugDesde);
    if (!origen) return;
    let tocado = slug.value !== '';
    slug.addEventListener('input', () => { tocado = slug.value !== ''; });
    origen.addEventListener('input', () => { if (!tocado) slug.placeholder = slugify(origen.value); });
    if (!tocado && origen.value) slug.placeholder = slugify(origen.value);
  });

  // Vista previa de imágenes elegidas
  $$('input[type="file"][data-vista-previa]').forEach((input) => {
    let caja = $('[data-vista-previa-de="' + input.id + '"]');
    if (!caja) {
      caja = document.createElement('div');
      caja.className = 'vista-previa';
      input.insertAdjacentElement('afterend', caja);
    }
    input.addEventListener('change', () => {
      caja.innerHTML = '';
      Array.from(input.files || []).slice(0, 12).forEach((f) => {
        if (!f.type.startsWith('image/')) return;
        const img = document.createElement('img');
        img.alt = f.name;
        img.src = URL.createObjectURL(f);
        img.onload = () => URL.revokeObjectURL(img.src);
        caja.appendChild(img);
      });
    });
  });

  // Editor de características
  const specs = $('[data-specs]');
  const agregar = $('[data-spec-agregar]');
  if (specs && agregar) {
    agregar.addEventListener('click', () => {
      const modelo = $('[data-spec-fila]', specs);
      const fila = modelo.cloneNode(true);
      $$('input', fila).forEach((i) => { i.value = ''; });
      specs.appendChild(fila);
      $('input', fila).focus();
    });
    specs.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-spec-quitar]');
      if (!btn) return;
      const filas = $$('[data-spec-fila]', specs);
      const fila = btn.closest('[data-spec-fila]');
      if (filas.length > 1) fila.remove();
      else $$('input', fila).forEach((i) => { i.value = ''; });
    });
  }

  // Precio: muestra el % de descuento mientras se escribe
  const precio = $('[data-precio]');
  const lista = $('[data-precio-lista]');
  const nota = $('[data-descuento]');
  if (precio && lista && nota) {
    const textoBase = nota.textContent;
    const num = (v) => {
      v = (v || '').replace(/[^\d,.]/g, '');
      if (v.includes(',')) v = v.replace(/\./g, '').replace(',', '.');
      else if ((v.match(/\./g) || []).length > 1 || /^\d{1,3}\.\d{3}$/.test(v)) v = v.replace(/\./g, '');
      return parseFloat(v);
    };
    const actualizar = () => {
      const p = num(precio.value);
      const l = num(lista.value);
      if (p > 0 && l > p) nota.textContent = 'Se va a mostrar ' + Math.round((1 - p / l) * 100) + '% OFF.';
      else if (l > 0 && p >= l) nota.textContent = 'Tiene que ser mayor que el precio de venta para mostrarse tachado.';
      else nota.textContent = textoBase;
    };
    precio.addEventListener('input', actualizar);
    lista.addEventListener('input', actualizar);
    actualizar();
  }
})();
