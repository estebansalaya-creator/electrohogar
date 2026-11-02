<?php
/**
 * Correos: bandeja de salida (tabla "correos") + envío por SMTP.
 *
 * Todo correo se guarda primero en la bandeja. Si el SMTP está configurado se envía en el momento;
 * si no, queda como "sin_configurar" y se puede ver (y reenviar después) desde el panel.
 *
 * Configuración (panel > Correos, o constantes MAIL_* en config.local.php):
 *   correo.host, correo.puerto, correo.seguridad (ssl | tls | ninguna), correo.usuario, correo.clave,
 *   correo.remitente, correo.remitente_nombre, correo.avisos (a dónde llegan los avisos del local),
 *   correo.verificar_ssl ('0' para servidores con certificado propio)
 */
declare(strict_types=1);

const CORREO_MAX_INTENTOS = 5;

function correo_config(): array
{
    $c = (array) config('correo', []);
    $seg = strtolower((string) ($c['seguridad'] ?? 'tls'));
    return [
        'host'             => trim((string) ($c['host'] ?? '')),
        'puerto'           => (int) ($c['puerto'] ?? 0) ?: ($seg === 'ssl' ? 465 : ($seg === 'tls' ? 587 : 25)),
        'seguridad'        => in_array($seg, ['ssl', 'tls', 'ninguna'], true) ? $seg : 'tls',
        'usuario'          => (string) ($c['usuario'] ?? ''),
        'clave'            => (string) (descifrar(isset($c['clave']) ? (string) $c['clave'] : null) ?? ''),
        'remitente'        => trim((string) ($c['remitente'] ?? '')) ?: trim((string) ($c['usuario'] ?? '')),
        'remitente_nombre' => trim((string) ($c['remitente_nombre'] ?? '')) ?: tienda('nombre'),
        'avisos'           => trim((string) ($c['avisos'] ?? '')) ?: tienda('email'),
        'verificar_ssl'    => ($c['verificar_ssl'] ?? '1') !== '0',
    ];
}

function correo_configurado(): bool
{
    $c = correo_config();
    return $c['host'] !== '' && filter_var($c['remitente'], FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Arma un correo con una plantilla de app/views/correos, lo guarda en la bandeja y, si se puede, lo envía.
 * Devuelve el id en la bandeja.
 */
function correo_encolar(string $tipo, string $para, string $asunto, string $plantilla, array $datos = [], ?string $referencia = null): int
{
    $html = correo_render($plantilla, $datos + ['asunto' => $asunto]);
    q(
        'INSERT INTO correos (tipo, referencia, para, asunto, html, texto, estado) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$tipo, $referencia, $para, mb_substr($asunto, 0, 190), $html, html_a_texto($html), correo_configurado() ? 'pendiente' : 'sin_configurar']
    );
    $id = (int) db()->lastInsertId();
    // Se intenta enviar en el momento. Si falla una vez, los siguientes de esta misma página quedan
    // pendientes (para no demorar al cliente) y se reintentan desde el panel o la tarea programada.
    static $fallo = false;
    if (correo_configurado() && !$fallo && !correo_enviar($id)) {
        $fallo = true;
    }
    return $id;
}

function correo_render(string $plantilla, array $datos): string
{
    $contenido = (function (string $__archivo, array $__datos): string {
        extract($__datos, EXTR_SKIP);
        ob_start();
        require $__archivo;
        return (string) ob_get_clean();
    })(APP_DIR . '/views/correos/' . $plantilla . '.php', $datos);

    return (function (string $__archivo, string $contenido, array $__datos): string {
        extract($__datos, EXTR_SKIP);
        ob_start();
        require $__archivo;
        return (string) ob_get_clean();
    })(APP_DIR . '/views/correos/layout.php', $contenido, $datos);
}

/** Versión texto del HTML (para clientes de correo que no muestran HTML). */
function html_a_texto(string $html): string
{
    $t = preg_replace('#<(style|head)[^>]*>.*?</\1>#si', '', $html) ?? $html;
    $t = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#si', '$2 ($1)', $t) ?? $t;
    $t = preg_replace('#<br\s*/?>|</(p|div|h[1-6]|li|table)>#i', "\n", $t) ?? $t;
    $t = preg_replace('#</tr>#i', "\n", $t) ?? $t;
    $t = preg_replace('#</t[dh]>#i', "  ", $t) ?? $t;
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace("/[ \t]+/", ' ', $t) ?? $t;
    $t = preg_replace("/ *\n */", "\n", $t) ?? $t;
    $t = preg_replace("/\n{3,}/", "\n\n", $t) ?? $t;
    return trim($t);
}

/** Envía un correo de la bandeja. Devuelve true si salió. */
function correo_enviar(int $id): bool
{
    $c = q_one('SELECT * FROM correos WHERE id = ?', [$id]);
    if (!$c || $c['estado'] === 'enviado') {
        return (bool) $c;
    }
    if (!correo_configurado()) {
        q("UPDATE correos SET estado = 'sin_configurar' WHERE id = ?", [$id]);
        return false;
    }
    $cfg = correo_config();
    try {
        $smtp = new Smtp($cfg['host'], $cfg['puerto'], $cfg['seguridad'], $cfg['usuario'], $cfg['clave'], $cfg['verificar_ssl']);
        $smtp->enviar($cfg['remitente'], $cfg['remitente_nombre'], $c['para'], $c['asunto'], $c['html'], $c['texto'], $cfg['avisos'] ?: null);
        q("UPDATE correos SET estado = 'enviado', enviado_at = NOW(), intentos = intentos + 1, error = NULL WHERE id = ?", [$id]);
        return true;
    } catch (Throwable $ex) {
        q("UPDATE correos SET estado = 'error', intentos = intentos + 1, error = ? WHERE id = ?", [mb_substr($ex->getMessage(), 0, 2000), $id]);
        error_log('[Carlos Confort] Correo ' . $id . ': ' . $ex->getMessage());
        return false;
    }
}

/** Reintenta los pendientes y con error (de los últimos 3 días). Devuelve [enviados, fallidos]. */
function correo_procesar_pendientes(int $max = 20): array
{
    $max = max(1, min(100, $max));
    $ids = array_column(q_all(
        "SELECT id FROM correos WHERE estado IN ('pendiente', 'error') AND intentos < " . CORREO_MAX_INTENTOS . "
           AND created_at > (NOW() - INTERVAL 3 DAY) ORDER BY id LIMIT $max"
    ), 'id');
    $ok = $mal = 0;
    foreach ($ids as $id) {
        correo_enviar((int) $id) ? $ok++ : $mal++;
    }
    return [$ok, $mal];
}

/**
 * Cliente SMTP mínimo: SSL directo (465), STARTTLS (587) o sin cifrado; AUTH PLAIN/LOGIN; HTML + texto en UTF-8.
 */
final class Smtp
{
    /** @var resource|null */
    private $fp = null;
    private array $extensiones = [];

    public function __construct(
        private string $host,
        private int $puerto,
        private string $seguridad = 'tls',
        private string $usuario = '',
        private string $clave = '',
        private bool $verificarSsl = true,
        private int $timeout = 12,
    ) {
    }

    public function enviar(string $de, string $deNombre, string $para, string $asunto, string $html, string $texto, ?string $responderA = null): void
    {
        foreach ([$de, $para] as $dir) {
            if (!filter_var($dir, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Dirección de correo inválida: $dir");
            }
        }
        try {
            $this->conectar();
            $this->comando('MAIL FROM:<' . $de . '>', [250]);
            $this->comando('RCPT TO:<' . $para . '>', [250, 251]);
            $this->comando('DATA', [354]);
            $this->escribir($this->armarMensaje($de, $deNombre, $para, $asunto, $html, $texto, $responderA) . "\r\n.");
            $this->esperar([250]);
            $this->comando('QUIT', [221], false);
        } finally {
            if ($this->fp) {
                fclose($this->fp);
                $this->fp = null;
            }
        }
    }

    private function conectar(): void
    {
        $contexto = stream_context_create(['ssl' => [
            'verify_peer'       => $this->verificarSsl,
            'verify_peer_name'  => $this->verificarSsl,
            'allow_self_signed' => !$this->verificarSsl,
            'SNI_enabled'       => true,
            'peer_name'         => $this->host,
        ]]);
        $remoto = ($this->seguridad === 'ssl' ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->puerto;
        $fp = @stream_socket_client($remoto, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $contexto);
        if (!$fp) {
            throw new RuntimeException("No se pudo conectar a {$this->host}:{$this->puerto} ($errstr). Revisá servidor, puerto y seguridad.");
        }
        stream_set_timeout($fp, $this->timeout);
        $this->fp = $fp;
        $this->esperar([220]);
        $this->ehlo();

        if ($this->seguridad === 'tls') {
            if (!isset($this->extensiones['STARTTLS'])) {
                throw new RuntimeException('El servidor no ofrece STARTTLS. Probá con seguridad SSL (puerto 465).');
            }
            $this->comando('STARTTLS', [220]);
            $metodo = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $metodo |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $metodo |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            if (!@stream_socket_enable_crypto($this->fp, true, $metodo)) {
                throw new RuntimeException('No se pudo iniciar la conexión segura (STARTTLS). Revisá el certificado del servidor.');
            }
            $this->ehlo();
        }

        if ($this->usuario !== '') {
            $auth = strtoupper($this->extensiones['AUTH'] ?? '');
            if (str_contains($auth, 'PLAIN')) {
                $this->comando('AUTH PLAIN ' . base64_encode("\0" . $this->usuario . "\0" . $this->clave), [235], true, 'Usuario o contraseña incorrectos');
            } else {
                $this->comando('AUTH LOGIN', [334]);
                $this->comando(base64_encode($this->usuario), [334], true, 'Usuario incorrecto', true);
                $this->comando(base64_encode($this->clave), [235], true, 'Usuario o contraseña incorrectos', true);
            }
        }
    }

    private function ehlo(): void
    {
        $nombre = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost')) ?: 'localhost';
        $lineas = $this->comando('EHLO ' . $nombre, [250]);
        $this->extensiones = [];
        foreach (array_slice($lineas, 1) as $l) {
            $partes = explode(' ', strtoupper(trim(substr($l, 4))), 2);
            $this->extensiones[$partes[0]] = $partes[1] ?? '';
        }
    }

    /** @return string[] líneas de la respuesta */
    private function comando(string $linea, array $codigos, bool $estricto = true, string $mensaje = '', bool $secreto = false): array
    {
        $this->escribir($linea);
        // Nunca se guarda en el error lo que lleva usuario o contraseña
        return $this->esperar($codigos, $estricto, $mensaje, $secreto || str_starts_with($linea, 'AUTH') ? 'AUTH ...' : $linea);
    }

    private function escribir(string $datos): void
    {
        if (@fwrite($this->fp, $datos . "\r\n") === false) {
            throw new RuntimeException('Se cortó la conexión con el servidor de correo.');
        }
    }

    private function esperar(array $codigos, bool $estricto = true, string $mensaje = '', string $enviado = ''): array
    {
        $lineas = [];
        while (($l = fgets($this->fp, 1024)) !== false) {
            $lineas[] = rtrim($l, "\r\n");
            if (strlen($l) < 4 || $l[3] === ' ') {
                break;
            }
        }
        $meta = stream_get_meta_data($this->fp);
        if (!$lineas) {
            throw new RuntimeException(!empty($meta['timed_out']) ? 'El servidor de correo no respondió a tiempo.' : 'El servidor de correo cerró la conexión.');
        }
        $codigo = (int) substr($lineas[count($lineas) - 1], 0, 3);
        if ($estricto && !in_array($codigo, $codigos, true)) {
            $detalle = implode(' ', $lineas);
            throw new RuntimeException(($mensaje ? $mensaje . '. ' : '') . 'El servidor respondió: ' . $detalle . ($enviado ? " (a $enviado)" : ''));
        }
        return $lineas;
    }

    private function armarMensaje(string $de, string $deNombre, string $para, string $asunto, string $html, string $texto, ?string $responderA): string
    {
        $limite = 'cc-' . bin2hex(random_bytes(12));
        $dominio = substr(strrchr($de, '@') ?: '@localhost', 1);
        $cab = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->codificar($deNombre) . ' <' . $de . '>',
            'To: <' . $para . '>',
            'Subject: ' . $this->codificar($asunto),
            'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $dominio . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $limite . '"',
        ];
        if ($responderA && filter_var($responderA, FILTER_VALIDATE_EMAIL) && strcasecmp($responderA, $de) !== 0) {
            $cab[] = 'Reply-To: <' . $responderA . '>';
        }
        $parte = fn(string $tipo, string $cuerpo) => "--$limite\r\nContent-Type: $tipo; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($cuerpo), 76, "\r\n")) . "\r\n";
        return implode("\r\n", $cab) . "\r\n\r\n"
            . $parte('text/plain', $texto)
            . $parte('text/html', $html)
            . "--$limite--";
    }

    private function codificar(string $texto): string
    {
        return preg_match('/[^\x20-\x7E]/', $texto) ? '=?UTF-8?B?' . base64_encode($texto) . '?=' : $texto;
    }
}
