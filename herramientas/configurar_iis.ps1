<#
.SYNOPSIS
  Configura la tienda en IIS (Windows), por defecto en http://localhost:8050

.DESCRIPTION
  1. Verifica que IIS y el módulo CGI/FastCGI estén instalados (si faltan, los instala).
  2. Detecta el PHP que IIS ya usa (o lo busca) y lo registra en FastCGI si hace falta.
     No modifica php.ini: si falta una extensión, avisa.
  3. Crea el grupo de aplicaciones y el sitio apuntando a <carpeta>\public en el puerto indicado.
  4. Da permiso de lectura sobre la carpeta y de escritura sobre storage\sesiones (carrito).
  5. Crea app\config.php a partir del ejemplo si no existe.
  6. Prueba la conexión a MySQL y que el sitio responda.
  Se puede correr varias veces: si el sitio ya existe, lo actualiza.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File D:\electro\herramientas\configurar_iis.ps1

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File D:\electro\herramientas\configurar_iis.ps1 -DbUsuario electrohogar_web -DbClave "xxxx" -AbrirFirewall
#>
[CmdletBinding()]
param(
    [int]$Puerto = 8050,
    [string]$Sitio = 'electrohogar',
    [string]$Carpeta = '',
    [string]$PhpCgi = '',
    [string]$DbUsuario = '',
    [string]$DbClave = '',
    [switch]$AbrirFirewall
)

$ErrorActionPreference = 'Stop'

function Paso([string]$t)  { Write-Host ''; Write-Host "==> $t" -ForegroundColor Cyan }
function Ok([string]$t)    { Write-Host "    OK  $t" -ForegroundColor Green }
function Aviso([string]$t) { Write-Host "    !!  $t" -ForegroundColor Yellow }
function Falla([string]$t) { Write-Host "    XX  $t" -ForegroundColor Red; exit 1 }

# ------------------------------------------------------------------
# Requisitos básicos
# ------------------------------------------------------------------
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Falla 'Hay que correrlo como administrador (clic derecho en PowerShell > Ejecutar como administrador, o usar configurar_iis.bat).'
}

if (-not $Carpeta) { $Carpeta = Split-Path -Parent $PSScriptRoot }
$Carpeta = (Resolve-Path $Carpeta).Path
$Publica = Join-Path $Carpeta 'public'
if (-not (Test-Path (Join-Path $Publica 'index.php'))) {
    Falla "No encuentro $Publica\index.php. Indicá la carpeta del proyecto con -Carpeta D:\electro"
}
$Pool = $Sitio
$appcmd = Join-Path $env:windir 'System32\inetsrv\appcmd.exe'

Write-Host "Configurando '$Sitio' -> $Publica (puerto $Puerto)" -ForegroundColor White

# Ejecuta appcmd y devuelve la salida como texto. Si falla, corta el script.
function Invoke-Appcmd {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]]$Argumentos)
    $anterior = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $salida = & $appcmd @Argumentos 2>&1 | Out-String
    $codigo = $LASTEXITCODE
    $ErrorActionPreference = $anterior
    if ($codigo -ne 0) { throw "appcmd $($Argumentos -join ' ') falló: $salida" }
    return $salida
}

# Devuelve $true si existe el objeto de IIS (site, apppool).
function Test-Appcmd([string]$tipo, [string]$nombre) {
    $anterior = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $salida = & $appcmd list $tipo "/name:$nombre" 2>&1 | Out-String
    $codigo = $LASTEXITCODE
    $ErrorActionPreference = $anterior
    return ($codigo -eq 0 -and $salida.Trim() -ne '')
}

# Corre un comando sin cortar el script si falla (por ejemplo, iniciar algo ya iniciado).
function Intentar([scriptblock]$bloque) {
    try { & $bloque | Out-Null } catch { }
}

# ------------------------------------------------------------------
# 1. IIS y FastCGI
# ------------------------------------------------------------------
Paso 'IIS y módulo CGI (FastCGI)'
$esServidor = (Get-CimInstance Win32_OperatingSystem).ProductType -ne 1

function Instalar-IIS {
    if ($esServidor) {
        Install-WindowsFeature -Name Web-Server, Web-Default-Doc, Web-Static-Content, Web-Http-Errors, Web-Filtering, Web-CGI, Web-Mgmt-Console | Out-Null
    } else {
        $caracteristicas = @('IIS-WebServerRole', 'IIS-WebServer', 'IIS-CommonHttpFeatures', 'IIS-DefaultDocument',
            'IIS-StaticContent', 'IIS-HttpErrors', 'IIS-RequestFiltering', 'IIS-ApplicationDevelopment', 'IIS-CGI',
            'IIS-ManagementConsole')
        Enable-WindowsOptionalFeature -Online -FeatureName $caracteristicas -All -NoRestart | Out-Null
    }
}

if (-not (Test-Path $appcmd)) {
    Aviso 'IIS no está instalado. Instalándolo (puede tardar unos minutos)...'
    Instalar-IIS
    if (-not (Test-Path $appcmd)) { Falla 'No se pudo instalar IIS. Puede hacer falta reiniciar la PC y volver a correr el script.' }
}
$modulos = Invoke-Appcmd list modules
if ($modulos -notmatch 'FastCgiModule') {
    Aviso 'Falta el módulo CGI. Instalándolo...'
    Instalar-IIS
    $modulos = Invoke-Appcmd list modules
    if ($modulos -notmatch 'FastCgiModule') {
        Falla 'No se pudo activar CGI. Activalo a mano: Características de Windows > IIS > Servicios WWW > Características de desarrollo de aplicaciones > CGI.'
    }
}
Ok 'IIS con FastCGI instalado'

# ------------------------------------------------------------------
# 2. PHP
# ------------------------------------------------------------------
Paso 'PHP'
$handlersServidor = [xml](Invoke-Appcmd list config /section:system.webServer/handlers)
$handlerPhp = $handlersServidor.SelectNodes("//handlers/add[@path='*.php']") | Select-Object -First 1
$phpDelServidor = if ($handlerPhp) { ($handlerPhp.GetAttribute('scriptProcessor') -split '\|')[0] } else { '' }

if (-not $PhpCgi -and $phpDelServidor) {
    $PhpCgi = $phpDelServidor
    Ok "IIS ya tiene PHP configurado: $PhpCgi"
}
if (-not $PhpCgi) {
    $candidatos = @(
        "$env:ProgramFiles\PHP\*\php-cgi.exe", "$env:ProgramFiles\PHP\php-cgi.exe",
        'C:\php\php-cgi.exe', 'C:\php*\php-cgi.exe', 'C:\tools\php*\php-cgi.exe',
        'C:\xampp\php\php-cgi.exe', 'C:\laragon\bin\php\*\php-cgi.exe'
    )
    foreach ($c in $candidatos) {
        $f = Get-ChildItem -Path $c -ErrorAction SilentlyContinue | Sort-Object FullName -Descending | Select-Object -First 1
        if ($f) { $PhpCgi = $f.FullName; break }
    }
    if (-not $PhpCgi) {
        $cmd = Get-Command php-cgi.exe -ErrorAction SilentlyContinue
        if ($cmd) { $PhpCgi = $cmd.Source }
    }
}
if (-not $PhpCgi -or -not (Test-Path ($PhpCgi -split '\|')[0])) {
    Falla 'No encontré PHP. Instalá PHP 8.1 o superior (versión NTS x64 de https://windows.php.net/download) y corré el script con -PhpCgi "C:\ruta\php-cgi.exe".'
}
$PhpCgi = (Resolve-Path ($PhpCgi -split '\|')[0]).Path
$phpDir = Split-Path -Parent $PhpCgi
$phpExe = Join-Path $phpDir 'php.exe'

# Versión y extensiones (sin tocar php.ini)
$versionTexto = (& $PhpCgi -v 2>&1 | Out-String)
if ($versionTexto -match 'PHP (\d+)\.(\d+)\.(\d+)') {
    $version = [version]"$($Matches[1]).$($Matches[2]).$($Matches[3])"
    if ($version -lt [version]'8.1.0') { Falla "PHP $version es viejo: el sitio necesita PHP 8.1 o superior." }
    Ok "PHP $version ($PhpCgi)"
} else {
    Aviso "No pude leer la versión de PHP: $versionTexto"
}
$extensiones = (& $PhpCgi -m 2>&1 | Out-String)
$faltan = @('pdo_mysql', 'mbstring') | Where-Object { $extensiones -notmatch "(?m)^$_\s*$" }
if ($faltan) {
    $ini = (& $PhpCgi --ini 2>&1 | Out-String)
    Aviso "Faltan extensiones de PHP: $($faltan -join ', ')."
    Aviso "Abrí el php.ini (ver abajo), sacale el ; a las líneas extension=pdo_mysql y extension=mbstring, y volvé a correr el script."
    Write-Host $ini
} else {
    Ok 'Extensiones pdo_mysql y mbstring activas'
}
if ($extensiones -notmatch '(?m)^gd\s*$') {
    Aviso 'Falta la extensión gd: las fotos subidas desde el panel se guardan sin achicar. Para activarla, sacale el ; a extension=gd en php.ini.'
}

# Límite de subida de archivos (fotos desde el panel)
function ConvertirA-Bytes([string]$v) {
    $v = $v.Trim().ToUpper()
    $n = [double]('0' + ($v -replace '[^\d.]', ''))
    if ($v.EndsWith('G')) { return $n * 1GB }
    if ($v.EndsWith('M')) { return $n * 1MB }
    if ($v.EndsWith('K')) { return $n * 1KB }
    return $n
}
if (Test-Path $phpExe) {
    $limites = ((& $phpExe -r "echo ini_get('upload_max_filesize'), '|', ini_get('post_max_size');" 2>&1 | Out-String).Trim()) -split '\|'
    if ($limites.Count -eq 2) {
        if ((ConvertirA-Bytes $limites[0]) -lt 8MB -or (ConvertirA-Bytes $limites[1]) -lt 24MB) {
            Aviso "PHP acepta archivos de hasta $($limites[0]) (upload_max_filesize) y envíos de hasta $($limites[1]) (post_max_size)."
            Aviso 'Las fotos de celular suelen pesar más. Recomendado en php.ini: upload_max_filesize = 10M y post_max_size = 40M, y después iisreset.'
        } else {
            Ok "Subida de archivos: hasta $($limites[0]) por foto"
        }
    }
}

# Registrar php-cgi en FastCGI si no está
$fastCgi = [xml](Invoke-Appcmd list config /section:system.webServer/fastCgi)
$registrado = $fastCgi.SelectNodes('//fastCgi/application') | Where-Object { $_.GetAttribute('fullPath') -ieq $PhpCgi }
if (-not $registrado) {
    Invoke-Appcmd set config /section:system.webServer/fastCgi "/+[fullPath='$PhpCgi',instanceMaxRequests='10000',activityTimeout='600',requestTimeout='600']" /commit:apphost | Out-Null
    Invoke-Appcmd set config /section:system.webServer/fastCgi "/+[fullPath='$PhpCgi'].environmentVariables.[name='PHP_FCGI_MAX_REQUESTS',value='10000']" /commit:apphost | Out-Null
    Ok 'php-cgi registrado en FastCGI'
} else {
    Ok 'php-cgi ya estaba registrado en FastCGI'
}

# Si el servidor no tiene ningún manejador para *.php, lo agrega a nivel servidor
if (-not $phpDelServidor) {
    Invoke-Appcmd set config /section:system.webServer/handlers "/+[name='PHP_via_FastCGI',path='*.php',verb='GET,HEAD,POST',modules='FastCgiModule',scriptProcessor='$PhpCgi',resourceType='Either']" /commit:apphost | Out-Null
    $phpDelServidor = $PhpCgi
    Ok 'Manejador de *.php agregado en IIS'
}

# ------------------------------------------------------------------
# 3. Puerto libre
# ------------------------------------------------------------------
Paso "Puerto $Puerto"
$sitios = (Invoke-Appcmd list site) -split "`r?`n"
$ocupadoPor = $sitios | Where-Object { $_ -match ":${Puerto}:" -and $_ -notmatch ('^SITE "' + [regex]::Escape($Sitio) + '"') }
if ($ocupadoPor) { Falla "El puerto $Puerto ya lo usa otro sitio de IIS: $ocupadoPor" }
$escuchando = Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.OwningProcess -ne 4 }
if ($escuchando) {
    $proceso = Get-Process -Id $escuchando[0].OwningProcess -ErrorAction SilentlyContinue
    Falla "El puerto $Puerto lo está usando otro programa: $($proceso.ProcessName) (PID $($escuchando[0].OwningProcess))."
}
Ok 'Libre'

# ------------------------------------------------------------------
# 4. Grupo de aplicaciones y sitio
# ------------------------------------------------------------------
Paso "Grupo de aplicaciones '$Pool'"
if (Test-Appcmd 'apppool' $Pool) {
    Ok 'Ya existía'
} else {
    Invoke-Appcmd add apppool "/name:$Pool" '/managedPipelineMode:Integrated' | Out-Null
    Ok 'Creado'
}
# Sin .NET (sitio PHP) e identidad propia del grupo
Invoke-Appcmd set apppool "/apppool.name:$Pool" '/managedRuntimeVersion:' '/processModel.identityType:ApplicationPoolIdentity' | Out-Null

Paso "Sitio '$Sitio'"
$binding = "http/*:${Puerto}:"
if (Test-Appcmd 'site' $Sitio) {
    Invoke-Appcmd set site "/site.name:$Sitio" "/bindings:$binding" | Out-Null
    Invoke-Appcmd set vdir "$Sitio/" "/physicalPath:$Publica" | Out-Null
    Ok 'Ya existía: actualicé el puerto y la carpeta'
} else {
    Invoke-Appcmd add site "/name:$Sitio" "/physicalPath:$Publica" "/bindings:$binding" | Out-Null
    Ok 'Creado'
}
Invoke-Appcmd set app "$Sitio/" "/applicationPool:$Pool" | Out-Null

# Si se pidió un PHP distinto al del servidor, el sitio usa el suyo
if ($phpDelServidor -and ($phpDelServidor -ine $PhpCgi)) {
    $handlersSitio = [xml](Invoke-Appcmd list config $Sitio /section:system.webServer/handlers)
    $propio = $handlersSitio.SelectNodes("//handlers/add[@name='PHP_$Sitio']")
    if ($propio.Count -gt 0) {
        Invoke-Appcmd set config $Sitio /section:system.webServer/handlers "/-[name='PHP_$Sitio']" /commit:apphost | Out-Null
    }
    Invoke-Appcmd set config $Sitio /section:system.webServer/handlers "/+[@start,name='PHP_$Sitio',path='*.php',verb='GET,HEAD,POST',modules='FastCgiModule',scriptProcessor='$PhpCgi',resourceType='Either']" /commit:apphost | Out-Null
    Ok "El sitio usa su propio PHP: $PhpCgi"
}

# Los visitantes anónimos usan la identidad del grupo de aplicaciones
Invoke-Appcmd set config $Sitio /section:system.webServer/security/authentication/anonymousAuthentication '/userName:' '/password:' /commit:apphost | Out-Null
Ok "http://localhost:$Puerto -> $Publica"

# ------------------------------------------------------------------
# 5. Permisos y carpeta de sesiones
# ------------------------------------------------------------------
Paso 'Permisos'
$identidad = "IIS AppPool\$Pool"
& icacls $Carpeta /grant "${identidad}:(OI)(CI)RX" /grant 'IUSR:(OI)(CI)RX' /grant 'IIS_IUSRS:(OI)(CI)RX' /Q | Out-Null
if ($LASTEXITCODE -ne 0) { Falla "No pude dar permisos de lectura sobre $Carpeta" }
Ok "Lectura sobre $Carpeta"

$storage = Join-Path $Carpeta 'storage'
$sesiones = Join-Path $storage 'sesiones'
New-Item -ItemType Directory -Path $sesiones -Force | Out-Null
& icacls $storage /grant "${identidad}:(OI)(CI)M" /grant 'IUSR:(OI)(CI)M' /Q | Out-Null
if ($LASTEXITCODE -ne 0) { Falla "No pude dar permisos de escritura sobre $storage" }
Ok "Escritura sobre $sesiones (sesiones del carrito)"

$subidas = Join-Path $Publica 'assets\img\subidas'
New-Item -ItemType Directory -Path $subidas -Force | Out-Null
& icacls $subidas /grant "${identidad}:(OI)(CI)M" /grant 'IUSR:(OI)(CI)M' /Q | Out-Null
if ($LASTEXITCODE -ne 0) { Falla "No pude dar permisos de escritura sobre $subidas" }
Ok "Escritura sobre $subidas (fotos subidas desde el panel)"

# ------------------------------------------------------------------
# 6. app\config.php
# ------------------------------------------------------------------
Paso 'Configuración (app\config.php)'
$config = Join-Path $Carpeta 'app\config.php'
$utf8SinBom = New-Object System.Text.UTF8Encoding($false)   # PHP no tolera el BOM al principio del archivo
if (-not (Test-Path $config)) {
    $texto = [IO.File]::ReadAllText((Join-Path $Carpeta 'app\config.example.php'), $utf8SinBom)
    # Escapa para string PHP entre comillas simples y para el reemplazo de -replace ($ es especial)
    function Para-Php([string]$v) { return $v.Replace('\', '\\').Replace("'", "\'").Replace('$', '$$') }
    if ($DbUsuario) { $texto = $texto -replace "'user'\s*=>\s*'[^']*'", ("'user'    => '" + (Para-Php $DbUsuario) + "'") }
    if ($DbClave)   { $texto = $texto -replace "'pass'\s*=>\s*'[^']*'", ("'pass'    => '" + (Para-Php $DbClave) + "'") }
    [IO.File]::WriteAllText($config, $texto, $utf8SinBom)
    Ok 'Creado a partir de app\config.example.php'
    if (-not $DbClave) { Aviso 'Completá usuario y contraseña de MySQL en app\config.php (o volvé a correr con -DbUsuario y -DbClave después de borrarlo).' }
    Aviso 'Completá también los datos del local: teléfono, WhatsApp, dirección, horario, CUIT.'
} else {
    Ok 'Ya existe (no lo modifico)'
}

# ------------------------------------------------------------------
# 7. Firewall (opcional)
# ------------------------------------------------------------------
Paso 'Firewall'
if ($AbrirFirewall) {
    $regla = "IIS $Sitio (TCP $Puerto)"
    if (-not (Get-NetFirewallRule -DisplayName $regla -ErrorAction SilentlyContinue)) {
        New-NetFirewallRule -DisplayName $regla -Direction Inbound -Protocol TCP -LocalPort $Puerto -Action Allow -Profile Domain, Private | Out-Null
    }
    Ok "Puerto $Puerto abierto para la red local (perfiles Dominio y Privado)"
} else {
    Ok "Sin cambios: el sitio se ve desde esta PC. Para verlo desde otras PCs de la red, volvé a correr con -AbrirFirewall"
}

# ------------------------------------------------------------------
# 8. Arranque y prueba
# ------------------------------------------------------------------
Paso 'Prueba'
Intentar { Invoke-Appcmd start apppool "/apppool.name:$Pool" }
Intentar { Invoke-Appcmd start site "/site.name:$Sitio" }

if (Test-Path $phpExe) {
    $bootstrap = (Join-Path $Carpeta 'app\bootstrap.php') -replace '\\', '/'
    $anterior = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $prueba = (& $phpExe -r "require '$bootstrap'; db()->query('SELECT 1'); echo 'ok';" 2>&1 | Out-String).Trim()
    $ErrorActionPreference = $anterior
    if ($prueba -eq 'ok') { Ok 'PHP conecta a MySQL' } else { Aviso "PHP no conecta a MySQL: $prueba" }
}

$url = "http://localhost:$Puerto/"
try {
    $respuesta = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 60
    Ok "El sitio responde (HTTP $($respuesta.StatusCode))"
    Write-Host ''
    Write-Host "Listo: $url" -ForegroundColor Green
    Write-Host "Panel de administración: ${url}admin/  (el primer usuario se crea entrando desde esta PC)" -ForegroundColor Green
} catch {
    $codigoHttp = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 0 }
    Aviso "El sitio respondió con error (HTTP $codigoHttp)."
    Aviso "Para ver el detalle, poné 'debug' => true en app\config.php y abrí $url en el navegador."
    Aviso 'Si es 500.0 de FastCGI, revisá que php.ini tenga cgi.force_redirect = 0 y fastcgi.impersonate = 1.'
}
