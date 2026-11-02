<?php
/**
 * Configuración de la tienda.
 * Copiar este archivo como app/config.php y completar los datos.
 * app/config.php NO se publica: queda fuera de la carpeta public/.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'electrohogar',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'app' => [
        // true solo en desarrollo: muestra los errores en pantalla.
        'debug'    => false,
        // Vacío si el sitio está en la raíz (http://tienda.local/).
        // Si está en una subcarpeta/aplicación de IIS, por ejemplo http://servidor/electro/, poner '/electro'.
        'base_url' => '',
        // Dirección pública del sitio, para los enlaces de los emails. Ej: 'https://www.carlosconfort.com.ar'
        // Vacío: se usa la dirección con la que se está navegando.
        'url_publica' => '',
        // Llave para cifrar las credenciales guardadas desde el panel (Mercado Pago, contraseña del correo).
        // Mejor definirla como APP_KEY en config.local.php. Generarla con: php -r "echo base64_encode(random_bytes(32));"
        // Si se pierde o se cambia, hay que volver a cargar esas credenciales en el panel.
        'clave' => '',
        // Carpeta de sesiones (carrito). El script herramientas\configurar_iis.ps1 le da permiso de escritura.
        'session_path' => __DIR__ . '/../storage/sesiones',
        'timezone' => 'America/Argentina/Buenos_Aires',
    ],

    'tienda' => [
        'nombre'       => 'Carlos Confort',
        'telefono'     => '(011) 4000-0000',
        // WhatsApp en formato internacional, sin "+" ni espacios: 54 9 11 XXXX XXXX
        'whatsapp'     => '5491100000000',
        'email'        => 'ventas@carlosconfort.com.ar',
        'direccion'    => 'Av. Ejemplo 1234, Buenos Aires',
        'horario'      => 'Lunes a viernes de 9 a 18 h. Sábados de 9 a 13 h.',
        'instagram'    => 'https://www.instagram.com/',
        'razon_social' => '',
        'cuit'         => '',
    ],

    'ventas' => [
        // Descuento por pago con transferencia o depósito bancario (%).
        'descuento_transferencia' => 10,
        // Unidades máximas por producto en el carrito.
        'max_unidades'            => 10,
        // Productos por página en los listados.
        'por_pagina'              => 12,
    ],
];
