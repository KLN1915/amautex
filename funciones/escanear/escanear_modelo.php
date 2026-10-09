<?php
/**
 * Cliente de la API del lector de fichas ópticas (OMR).
 * El navegador nunca habla con la API: le manda la foto a Amautex y Amautex
 * la reenvía con el token, que solo existe en el servidor.
 */
defined('APP_NOMBRE') or exit;

const OMR_MAX_BYTES = 8 * 1024 * 1024;                     // igual que el límite de la API
const OMR_TIPOS     = ['image/jpeg', 'image/png', 'image/webp'];

// La conexión con el lector es UNA para todo el sistema. Se toma de lo que el
// superadmin guardó en Administración > Lector de fichas (tabla configuracion);
// si no hay nada guardado, de config/config.local.php (compatibilidad).
function omr_url(): string
{
    return Ajustes::valor('omr_url') ?: OMR_URL;
}

function omr_token(): string
{
    return Ajustes::secreto('omr_token') ?? OMR_TOKEN;
}

function omr_plantilla_defecto(): string
{
    return Ajustes::valor('omr_plantilla') ?: OMR_PLANTILLA;
}

/** ¿Está configurado el token? Sin él, la pantalla explica qué falta. */
function omr_configurado(): bool
{
    return omr_token() !== '';
}

/**
 * Llama a la API y devuelve `datos`. Cualquier problema se convierte en una
 * RuntimeException con un mensaje que se le puede mostrar al usuario.
 * `$url` y `$token` permiten probar una conexión antes de guardarla.
 */
function omr_peticion(string $metodo, string $ruta, ?array $campos = null, int $timeout = 90,
                      ?string $url = null, ?string $token = null): array
{
    $url   = $url ?? omr_url();
    $token = $token ?? omr_token();
    if ($token === '') {
        throw new RuntimeException('El lector de fichas no está configurado. El superadmin debe cargar el token en Administración › Lector de fichas.');
    }

    $op = [
        CURLOPT_URL            => rtrim($url, '/') . $ruta,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,                     // nunca se desactiva: el token viaja aquí
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    if ($metodo === 'POST') {
        $op[CURLOPT_POST]       = true;
        $op[CURLOPT_POSTFIELDS] = $campos ?? [];
    }
    // Certificados: si el php.ini apunta a un archivo que no existe (pasa en
    // instalaciones de Laragon), se usa el almacén de certificados del sistema.
    $caIni = (string) ini_get('curl.cainfo');
    if (defined('OMR_CA') && OMR_CA !== '') {
        $op[CURLOPT_CAINFO] = OMR_CA;
    } elseif ($caIni !== '' && !is_file($caIni)) {
        $op[CURLOPT_CAINFO]      = '';
        $op[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
    }

    $ch = curl_init();
    curl_setopt_array($ch, $op);
    $cuerpo = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($cuerpo === false) {
        registrar('OMR sin conexión: ' . $error, 'ERROR');
        throw new RuntimeException('No se pudo conectar con el lector de fichas. Intenta de nuevo en un momento.');
    }
    $json = json_decode($cuerpo, true);
    if (!is_array($json)) {
        registrar("OMR respuesta inválida (HTTP $codigo)", 'ERROR');
        throw new RuntimeException('El lector de fichas respondió algo inesperado.');
    }
    if (empty($json['ok'])) {
        $mensaje = match ($codigo) {
            401     => 'El token del lector de fichas no es válido o fue revocado.',
            429     => 'Se escanearon demasiadas fichas en un minuto. Espera un momento.',
            default => (string) ($json['mensaje'] ?? 'El lector de fichas no pudo procesar la imagen.'),
        };
        if ($codigo === 401) registrar('OMR rechazó el token', 'ERROR');
        throw new RuntimeException($mensaje);
    }
    return (array) ($json['datos'] ?? []);
}

/** Plantillas registradas en el lector (se guardan 10 minutos en la sesión). */
function omr_plantillas(): array
{
    $c = $_SESSION['_omr_plantillas'] ?? null;
    if ($c && $c['hasta'] > time()) return $c['lista'];
    $lista = omr_peticion('GET', '/plantillas', null, 20);
    $_SESSION['_omr_plantillas'] = ['lista' => $lista, 'hasta' => time() + 600];
    return $lista;
}

/** Revisa la foto subida antes de mandarla. Devuelve la fila de $_FILES. */
function omr_validar_foto(?array $f): array
{
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Toma o sube la foto de la ficha.');
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > OMR_MAX_BYTES) {
        throw new RuntimeException('La foto pesa demasiado (máximo 8 MB).');
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('La foto no llegó completa. Vuelve a intentarlo.');
    }
    $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);   // por contenido, no por extensión
    if (!in_array($tipo, OMR_TIPOS, true) || !@getimagesize($f['tmp_name'])) {
        throw new RuntimeException('El archivo no es una imagen JPG, PNG o WEBP.');
    }
    $f['mime'] = $tipo;
    return $f;
}

function omr_limpiar_clave($clave): string
{
    $c = strtoupper(preg_replace('/[\s,]+/', '', (string) $clave));
    if ($c !== '' && !preg_match('/^[A-J-]{1,300}$/', $c)) {
        throw new RuntimeException('La clave solo puede tener letras A–E (o "-" para anular una pregunta).');
    }
    return $c;
}

function omr_limpiar_plantilla($p): string
{
    $p = (string) $p;
    if ($p === '') return omr_plantilla_defecto();
    if (!preg_match('/^[a-z0-9_-]{1,40}$/', $p)) throw new RuntimeException('Plantilla inválida.');
    return $p;
}

/** Envía la foto al lector y devuelve el resultado (con la foto corregida). */
function omr_calificar(array $foto, string $clave, string $plantilla): array
{
    $nombre = preg_replace('/[^\w.\- ]/u', '_', basename((string) $foto['name'])) ?: 'ficha.jpg';
    return omr_peticion('POST', '/calificar', [
        'imagen'    => new CURLFile($foto['tmp_name'], $foto['mime'], $nombre),
        'plantilla' => $plantilla,
        'clave'     => $clave,
        'anotada'   => 'true',
    ]);
}
