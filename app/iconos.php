<?php
/**
 * Íconos SVG en línea (trazo 24x24, heredan el color del texto).
 * Uso: <?= icono('carrito') ?>
 */
declare(strict_types=1);

function icono(string $nombre, string $clase = ''): string
{
    static $paths = [
        'camion'        => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
        'lupa'          => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/>',
        'carrito'       => '<path d="M2.5 3.5h2.2l2.4 11.2h11.4l2-7.7H6"/><circle cx="9" cy="19.5" r="1.5"/><circle cx="17" cy="19.5" r="1.5"/>',
        'menu'          => '<path d="M4 6.5h16M4 12h16M4 17.5h16"/>',
        'ayuda'         => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.3a2.5 2.5 0 1 1 3.4 2.3c-.7.3-1 .9-1 1.6v.6"/><circle cx="12" cy="17" r=".6" fill="currentColor"/>',
        'whatsapp'      => '<path d="M4 20l1.2-3.9A8 8 0 1 1 8 19l-4 1z"/><path d="M9.2 8.6c.2-.4.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.6c.1.2 0 .5-.1.7l-.5.6c.6 1.2 1.5 2.1 2.7 2.7l.6-.5c.2-.2.5-.2.7-.1l1.6.7c.3.1.4.3.4.5v.5c0 .3-.1.6-.5.8-.7.4-1.6.5-2.6.1a9 9 0 0 1-4.9-4.9c-.4-1-.3-1.9.1-2.6z" fill="currentColor" stroke="none"/>',
        'instagram'     => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".9" fill="currentColor"/>',
        'telefono'      => '<path d="M5 4h3.5l1.6 4.2-2.2 1.4a11 11 0 0 0 6.5 6.5l1.4-2.2L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5A16 16 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4z"/>',
        'mail'          => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'ubicacion'     => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/>',
        'reloj'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
        'tarjeta'       => '<rect x="2.5" y="5.5" width="19" height="13" rx="2"/><path d="M2.5 10h19M6 15h4"/>',
        'transferencia' => '<path d="M4 9h14l-3.5-3.5M20 15H6l3.5 3.5"/>',
        'tienda'        => '<path d="M4 10v10h16V10M3 10l1.8-5.5h14.4L21 10z"/><path d="M9.5 20v-5.5h5V20"/>',
        'escudo'        => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.1 7.5 9.5 4.3-1.4 7.5-4.9 7.5-9.5V6z"/><path d="m8.8 12 2.2 2.2 4.3-4.4"/>',
        'cerrar'        => '<path d="M6 6l12 12M18 6 6 18"/>',
        'izq'           => '<path d="M15 5l-7 7 7 7"/>',
        'der'           => '<path d="M9 5l7 7-7 7"/>',
        'abajo'         => '<path d="M5.5 9 12 15.5 18.5 9"/>',
        'check'         => '<path d="M4.5 12.5 9.5 17.5 19.5 7"/>',
        'mas'           => '<path d="M12 5v14M5 12h14"/>',
        'menos'         => '<path d="M5 12h14"/>',
        'basura'        => '<path d="M4 7h16M9.5 7V4.5h5V7M6.5 7l1 13h9l1-13M10 11v5.5M14 11v5.5"/>',
        'filtro'        => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'cupon'         => '<path d="M3 8.5V6h18v2.5a3.5 3.5 0 0 0 0 7V18H3v-2.5a3.5 3.5 0 0 0 0-7z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
        'devolucion'    => '<path d="M4 9h11a5 5 0 0 1 0 10H8"/><path d="M8 5 4 9l4 4"/>',
    ];
    $p = $paths[$nombre] ?? '';
    return '<svg class="icon ' . e($clase) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}
