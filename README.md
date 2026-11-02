# Carlos Confort · Tienda online

Sitio de venta de electrodomésticos y hogar en PHP + MySQL, pensado para correr en IIS (Windows).
Estructura inspirada en tiendas tipo Faraone: barra superior, buscador, menú de categorías, carrusel de promociones,
tarjetas con precio tachado / precio con transferencia / cuotas sin interés, carrito y pie con legales.

**Etapa 1:** home, catálogo por categoría, búsqueda, filtros, ficha de producto, carrito con cupones y
cálculo de envío por código postal, pedido por WhatsApp, páginas institucionales, newsletter y Botón de arrepentimiento.

**Etapa 2:** panel de administración en `/admin` (productos con fotos y características, categorías, marcas, banners,
cupones, envíos, páginas, arrepentimientos, suscriptores, importación desde Excel y datos de la tienda).

**Etapa 3:** registro de pedidos (el cliente completa sus datos, el pedido queda guardado con número y se gestiona
desde el panel) y correos automáticos con bandeja de salida. Sin casilla configurada, los correos se guardan y se
pueden leer en el panel ("modo prueba").

**Credenciales de Mercado Pago:** sección del panel que solo usa el administrador principal (guardadas cifradas,
verificadas contra Mercado Pago, con contraseña, registro y aviso por email). El cobro en sí es la próxima etapa.

---

## Requisitos

- Windows con **IIS** y el rol **CGI** habilitado (Panel de control → Activar características de Windows → IIS → Servicios WWW → Características de desarrollo de aplicaciones → CGI).
- **PHP 8.1 o superior**, versión *Non Thread Safe x64*, registrado en IIS con FastCGI (lo más simple: *PHP Manager for IIS*).
  Extensiones necesarias en `php.ini`: `pdo_mysql` y `mbstring`. Recomendada: `gd` (achica las fotos que se suben desde el panel).
  Para subir fotos de celular: `upload_max_filesize = 10M` y `post_max_size = 40M` (después, `iisreset`).
- **MySQL 8** o **MariaDB 10.6+**.

## Instalación paso a paso

1. **Base de datos.** Cada cliente tiene su propia base. La de Carlos Confort es `electrohogar` (ya creada y cargada).
   Para un cliente nuevo, desde la carpeta del proyecto:
   ```
   mysql -u root -p -e "CREATE DATABASE nombre_base CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p nombre_base < database\schema.sql
   mysql -u root -p nombre_base < database\seed.sql
   ```
   `schema.sql` crea las tablas en la base indicada (si ya existen, **las borra** y las vuelve a crear: no correrlo sobre una base con datos).
   `seed.sql` carga categorías, banners, zonas de envío, páginas y **productos de ejemplo** (los textos de las páginas son de Carlos Confort: adaptarlos).

   **Bases creadas con una versión anterior** (como `electrohogar`): aplicar una vez, en orden, las migraciones que falten
   ```
   mysql -u root -p electrohogar < database\migraciones\001_panel_admin.sql
   mysql -u root -p electrohogar < database\migraciones\002_pedidos_correos.sql
   mysql -u root -p electrohogar < database\migraciones\003_mercadopago.sql
   ```
   (Una base nueva creada con `schema.sql` ya las incluye.)

2. **Configuración.** Copiar `app\config.example.php` como `app\config.php` (el script del paso 3 lo hace solo) y completar:
   - `db`: usuario y contraseña de MySQL.
   - `tienda`: teléfono, **WhatsApp** (formato `5491123456789`), email, dirección, horario, Instagram, razón social y CUIT.
   - `ventas.descuento_transferencia`: porcentaje de descuento por transferencia (10 por defecto).
   - `app.base_url`: vacío si el sitio está en la raíz; `'/electro'` si se publica como aplicación dentro de otro sitio.

   Opcional: `config.local.php` en la raíz del proyecto (`D:\electro\config.local.php`) con constantes de esta instalación.
   Si existe, sus valores tienen prioridad sobre `app\config.php`:
   ```php
   <?php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', 3306);
   define('DB_NAME', 'electrohogar');
   define('DB_USER', 'root');
   define('DB_PASS', '...');
   define('APP_TZ', 'America/Argentina/Buenos_Aires');
   define('APP_DEBUG', true);   // false en producción
   // define('APP_BASE_URL', '/electro');
   ```

3. **IIS (automático).** Doble clic en `herramientas\configurar_iis.bat` (pide permisos de administrador), o en PowerShell como administrador:
   ```
   powershell -ExecutionPolicy Bypass -File D:\electro\herramientas\configurar_iis.ps1
   ```
   El script:
   - instala IIS y CGI si faltan;
   - detecta el PHP que ya usa IIS y lo registra en FastCGI si hace falta (no modifica `php.ini`: si falta una extensión, avisa);
   - crea el grupo de aplicaciones y el sitio `electrohogar` en el **puerto 8050**, apuntando a `D:\electro\public`;
   - da permiso de lectura sobre la carpeta y de escritura sobre `storage` (sesiones e importaciones) y `public\assets\img\subidas` (fotos del panel);
   - crea `app\config.php` si no existe (con `-DbUsuario` y `-DbClave` completa la conexión a MySQL);
   - prueba la conexión a MySQL y que el sitio responda.

   Opciones: `-Puerto 8050`, `-Sitio electrohogar`, `-Carpeta D:\electro`, `-PhpCgi "C:\PHP\php-cgi.exe"`,
   `-DbUsuario`, `-DbClave` y `-AbrirFirewall` (para entrar desde otras PCs de la red local).
   Se puede correr varias veces: si el sitio ya existe, lo actualiza.

   Configuración manual equivalente: sitio con ruta física `D:\electro\public` (la carpeta `public`, no la raíz),
   grupo de aplicaciones *Sin código administrado* y permiso de escritura sobre `D:\electro\storage` y
   `D:\electro\public\assets\img\subidas` para `IIS AppPool\electrohogar`.

4. **Probar:** `http://localhost:8050`

5. **Panel:** entrar a `http://localhost:8050/admin` **desde la misma PC** y crear el primer usuario
   (por seguridad, desde otra PC no se puede). También se puede crear por comando:
   ```
   php herramientas\crear_admin.php tu@email.com "Tu nombre"
   ```
   El mismo comando sirve para cambiar la contraseña si alguien se la olvida.

**Otro cliente:** copiar la carpeta del proyecto (por ejemplo `D:\electro-cliente2`), crear su base, reemplazar el logo y la foto
en `public\assets\img\marca\` y correr el script con otro sitio y puerto:
`configurar_iis.ps1 -Sitio cliente2 -Puerto 8051 -Carpeta D:\electro-cliente2`.
Los colores de marca están en las primeras líneas de `public\assets\css\app.css`.

Para ver errores mientras se configura, poner `'debug' => true` en `app\config.php` (volver a `false` en producción).

## Cargar el catálogo real

Los productos de `seed.sql` son de demostración (sus códigos empiezan con `CC-`). Para cargar los reales:

1. Armar un Excel con las columnas de `database\plantilla_productos.csv` y guardarlo como **CSV (delimitado por punto y coma)**.
   Obligatorias: `sku`, `nombre`, `categoria`, `precio`. Opcionales: `rubro`, `marca`, `precio_lista`, `stock`, `cuotas`,
   `envio_gratis`, `destacado`, `imagen`, `descripcion`, `iva`, `activo`.
2. **Desde el panel:** *Importar productos* → subir el CSV → revisar el resultado → *Confirmar e importar*.

   **O por comando.** Probar sin guardar:
   ```
   php herramientas\importar_productos.php productos.csv --simular
   ```
   Importar:
   ```
   php herramientas\importar_productos.php productos.csv
   ```
   Si el SKU existe, actualiza (las celdas vacías no borran datos); si no, lo crea. Categorías y marcas nuevas se crean solas.
   Sirve también para actualizar precios y stock en masa.
3. Para quitar los productos de ejemplo antes de importar:
   ```sql
   DELETE FROM productos WHERE sku LIKE 'CC-%';
   ```

**Fotos:** lo más simple es subirlas desde el panel, en cada producto (se achican y convierten solas).
También se puede poner en la columna `imagen` del CSV una ruta dentro de `public\assets\img\`, por ejemplo
`productos/heladera-samsung-382.jpg`. Formato cuadrado, fondo claro.

## Qué se edita y dónde (panel `/admin`)

| Qué | Sección del panel |
|---|---|
| WhatsApp, teléfono, dirección, horario, CUIT, % transferencia | Datos de la tienda (pisan a `app\config.php`) |
| Productos, precios, stock, cuotas, fotos, características | Productos (precio y stock se editan también desde la lista) |
| "Lo más buscado" | Productos → Destacado |
| Carrusel de la home | Banners |
| Rubros, categorías y accesos rápidos del menú | Categorías |
| Cupones | Cupones. De prueba: `BIENVENIDA`, `CONFORT20MIL` (borrarlos antes de publicar) |
| Costos de envío por código postal | Envíos |
| Quiénes somos, Cómo comprar, Términos, etc. | Páginas (HTML). Completar los textos marcados con `[ ]` |
| Pedidos que entran por la web | Pedidos (estado, costo de envío, notas, historial, WhatsApp del cliente) |
| Casilla de correo y correos enviados | Correos (datos SMTP, prueba de envío, bandeja de salida) |
| Credenciales de Mercado Pago | Mercado Pago (solo el administrador principal) |
| Solicitudes de arrepentimiento | Arrepentimientos (estado y notas internas) |
| Suscriptores del newsletter | Suscriptores (se descargan para Excel) |
| Colores y estilos | `public\assets\css\app.css` (variables al principio) |

El inicio del panel avisa lo que falta antes de publicar (WhatsApp de ejemplo, CUIT, textos sin completar, productos de ejemplo,
casilla de correo sin configurar).

## Pedidos

1. El cliente arma el carrito y toca **Finalizar pedido**: completa nombre, teléfono, email (opcional) y elige
   **envío a domicilio** o **retiro en el local**. Los precios se toman siempre de la base, nunca del formulario.
2. El pedido queda guardado con número (`000001`) y el cliente ve una página con el estado y el botón para mandarlo por
   WhatsApp. Esa página (`pedido.php?t=...`) le sirve para seguirlo después.
3. En el panel (**Pedidos**) se cambia el estado: Nuevo → Confirmado → En preparación → Enviado (o "Listo para retirar")
   → Entregado, o Cancelado. **Al confirmar se descuenta el stock** y al cancelar (o volver a Nuevo) se devuelve.
   Se puede avisar al cliente por email con un mensaje opcional.
4. Si el código postal no tiene zona de envío, el envío queda "a cotizar": el costo se carga después en el pedido y el
   total se recalcula.

Límite: 6 pedidos por hora desde la misma conexión (frena envíos automáticos).

## Mercado Pago (credenciales)

- Solo el **administrador principal** ve la sección. Es el primer usuario creado; para pasarle el rol a otro usuario
  (desde el servidor): `php herramientas\crear_admin.php otro@email.com --principal`.
- Se cargan el **Access Token** (credenciales de producción, empieza con `APP_USR-`) y la **clave secreta** de webhooks.
  Antes de guardar se consulta a Mercado Pago de qué cuenta es el token y se rechaza si no es válido o es de otro país.
- Cada cambio pide la contraseña del panel, queda en "Cambios registrados" y manda un **aviso de seguridad** por email
  al principal y al email de avisos de la tienda. El panel nunca vuelve a mostrar el token: solo `APP_USR-…1234`.
- Se guardan **cifrados** en la base con la llave `APP_KEY` de `config.local.php` (también la contraseña del correo).
  Sin esa llave la sección no deja guardar. **No cambiarla ni perderla**: si cambia, hay que volver a cargar las
  credenciales. Para generar una: `php -r "echo base64_encode(random_bytes(32));"` y en `config.local.php`:
  `define('APP_KEY', 'la-llave-generada');`
- Requiere en php.ini la extensión `openssl` (también la usa el correo seguro) y, preferentemente, `curl`.

## Correos

Se generan solos: aviso de **pedido nuevo** al local, **detalle del pedido** al cliente, **cambio de estado** (si se
tilda el aviso), y **constancia de arrepentimiento** al cliente y al local. Todos quedan en **Panel → Correos**.

- **Sin casilla configurada** (modo prueba): se guardan como "Sin enviar" y se pueden ver tal cual le llegarían al cliente.
  Cuando se configure la casilla, se pueden mandar uno por uno con "Enviar ahora".
- **Configurar la casilla:** Panel → Correos → servidor SMTP, seguridad (SSL 465 o TLS 587), usuario, contraseña y
  remitente. Después, "Enviar prueba". Con Gmail hay que usar una contraseña de aplicación.
  También se puede dejar en `D:\electro\config.local.php` (lo del panel tiene prioridad):
  ```php
  define('MAIL_HOST', 'mail.carlosconfort.com.ar');
  define('MAIL_PORT', 465);
  define('MAIL_SECURE', 'ssl');          // ssl | tls | ninguna
  define('MAIL_USER', 'ventas@carlosconfort.com.ar');
  define('MAIL_PASS', '...');
  define('MAIL_NOTIFY', 'ventas@carlosconfort.com.ar');   // quién recibe los avisos
  define('APP_URL', 'https://www.carlosconfort.com.ar');   // para los enlaces de los correos
  ```
- **Reintentos:** si el servidor de correo falla, el correo queda "Con error" y se reintenta desde el panel
  ("Reintentar pendientes") o con una tarea programada de Windows cada 10 minutos:
  ```
  schtasks /Create /SC MINUTE /MO 10 /TN "Electrohogar correos" /TR "\"C:\PHP\php.exe\" D:\electro\herramientas\enviar_correos.php"
  ```
  (cambiar `C:\PHP\php.exe` por la ruta real de PHP).

## Probar en Railway

La misma aplicación corre en Railway con el `Dockerfile` incluido (PHP 8.3 + Apache). No hace falta tocar código:
la configuración sale de variables de entorno.

1. **Base de datos:** crear el MySQL en Railway y restaurar un dump de la base local (por ejemplo con DBeaver).
   El dump trae los usuarios del panel (se entra con el mismo usuario y contraseña), los datos de la tienda y los pedidos.
   - Las fotos subidas desde el panel **no** están en el dump: viven en `public\assets\img\subidas`. Subirlas de nuevo
     desde el panel de Railway (o copiarlas al volumen).
   - Las credenciales cifradas (Mercado Pago, contraseña del correo) solo se leen con la misma `APP_KEY`. Lo recomendable
     es usar otra llave en Railway y cargar ahí las credenciales de prueba.
   - Base vacía en lugar de dump: correr `database\schema.sql` (ya incluye todas las migraciones) y, si se quiere,
     `database\seed.sql` con los datos de ejemplo.
2. **Servicio de la tienda:** crearlo desde esta carpeta (`railway up`) o desde un repositorio. Railway usa el
   `Dockerfile` y `railway.json` (control de salud en `/salud.php`). `config.local.php`, `app\config.php` y `storage\`
   nunca se suben (`.gitignore` y `.dockerignore`).
3. **Variables del servicio** (`MySQL` es el nombre del servicio de la base en Railway):
   ```
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_USER=${{MySQL.MYSQLUSER}}
   DB_PASS=${{MySQL.MYSQLPASSWORD}}
   DB_NAME=railway                         (o el nombre de la base donde se hizo el restore)
   APP_KEY=...                             (php -r "echo base64_encode(random_bytes(32));")
   APP_URL=https://xxxx.up.railway.app     (la dirección que da Railway; para los enlaces de los correos)
   APP_PROXY=1                             (toma la IP real del cliente detrás del proxy de Railway)
   ```
   Si no se definen `DB_*`, se usan `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE` o `MYSQL_URL`.
4. **Volumen** para las fotos: montarlo en `/var/www/html/public/assets/img/subidas`. Sin volumen, las fotos se
   pierden en cada deploy. El contenedor le da permiso de escritura solo al arrancar.
5. **Dominio:** Settings → Networking → Generate Domain (puerto 8080).

Notas: Railway bloquea el correo por SMTP en los planes Free, Trial y Hobby (solo Pro lo permite); en esos planes los
correos quedan guardados en Panel → Correos. La dirección HTTPS de Railway sirve para probar los avisos de pago de
Mercado Pago. Las sesiones (carrito) se reinician en cada deploy.

## Estructura

```
app\              Código PHP (no se publica)
  bootstrap.php   Arranque: configuración, sesión, errores
  config.php      Datos de conexión y de la tienda (crear desde config.example.php)
  repositorio.php Consultas a la base
  carrito.php     Carrito en sesión (los precios siempre se leen de la base)
  pedidos.php     Registro de pedidos, estados y stock
  correo.php      Bandeja de correos y envío por SMTP (sin librerías externas)
  mercadopago.php Credenciales y llamadas a la API de Mercado Pago
  cifrado.php     Cifrado de credenciales con APP_KEY (AES-256-GCM)
  importador.php  Importación de productos desde CSV (comando y panel)
  imagenes.php    Subida de fotos (valida, achica y convierte a WebP)
  admin\          Panel: acceso, ABM genérico (entidades.php) y su motor (crud.php)
  views\          Plantillas HTML (views\admin\ para el panel, views\correos\ para los emails)
database\         schema.sql, seed.sql, plantilla CSV, migraciones\
herramientas\     configurar_iis.ps1/.bat, importar_productos.php, crear_admin.php, enviar_correos.php
docker\           apache.conf, php.ini y arranque.sh del contenedor (Railway); Dockerfile y railway.json en la raíz
public\           Raíz del sitio en IIS
  index.php, productos.php, producto.php, carrito.php, checkout.php, pedido.php, pagina.php, arrepentimiento.php
  admin\          Panel de administración
  api\            carrito.php, envio.php, newsletter.php (JSON)
  assets\         css, js, fuentes, imágenes (img\subidas\ = fotos subidas desde el panel)
storage\          Sesiones e importaciones (no se publica)
```

Seguridad del panel: contraseñas con hash, bloqueo de 15 minutos tras 8 intentos fallidos, token CSRF en todos los
formularios, sesión que vence a las 2 horas sin uso, y la carpeta de fotos subidas solo sirve imágenes.
Las credenciales de cobro las maneja solo el administrador principal, cifradas, con contraseña y registro de cambios.

## Notas legales (verificar con el contador / abogado)

- **Precio sin impuestos nacionales** (Ley 27.743 y Res. SIC 4/2025): se muestra en la ficha y en el carrito calculado
  como `precio / (1 + iva/100)`. Si algún producto tiene impuestos internos u otros tributos nacionales, ajustar el cálculo.
- **Cuotas:** se informa precio contado, cantidad y valor de cuotas y CFT 0,00% para planes sin interés.
- **Botón de arrepentimiento** (Res. SCI 424/2020): está en el pie de todas las páginas; guarda la solicitud y entrega
  un código de trámite. Las solicitudes se ven en el panel (con contador en el menú) y se manda la constancia por email
  al cliente y al local (cuando haya casilla configurada).
- **Defensa del consumidor:** enlace a la Ventanilla Única Federal en el pie.

## Próximas etapas sugeridas

1. Cobro con Mercado Pago (Checkout Pro) sobre el pedido ya registrado, con avisos de pago (webhooks). Para cobrar de
   verdad hace falta el dominio con HTTPS: Mercado Pago no puede avisar a `localhost`.
2. URLs amigables con IIS URL Rewrite (`/heladeras`, `/producto/heladera-no-frost-...`) y SEO.
3. Dominio propio y HTTPS.

## Créditos

Tipografía Archivo (Omnibus-Type), licencia SIL Open Font License: `public\assets\fonts\OFL-Archivo.txt`.
Las ilustraciones de productos en `public\assets\img\productos\*.svg` son provisorias hasta tener las fotos reales.
