<?php
/**
 * Módulo: Lector de fichas (solo superadmin).
 * Conexión ÚNICA con la API del lector OMR, compartida por todas las academias:
 * URL, token (se guarda cifrado y nunca vuelve al navegador) y hoja por defecto.
 */
defined('APP_NOMBRE') or exit;

require_once RUTA_FUNC . '/escanear/escanear_modelo.php';

/** Lo que la pantalla puede saber del ajuste actual: nunca el token completo. */
function lector_estado(): array
{
    $token = omr_token();
    return [
        'configurado' => $token !== '',
        'fuente'      => Ajustes::existe('omr_token') ? 'sistema' : (OMR_TOKEN !== '' ? 'archivo' : 'ninguna'),
        'final'       => $token !== '' ? substr($token, -4) : null,
        'legible'     => Ajustes::secretoLegible('omr_token'),
        'url'         => omr_url(),
        'plantilla'   => omr_plantilla_defecto(),
        'meta'        => Ajustes::meta('omr_token'),
    ];
}

function lector_url($u): string
{
    $u = rtrim(trim((string) $u), '/');
    $p = parse_url($u);
    $local = in_array($p['host'] ?? '', ['localhost', '127.0.0.1'], true);
    if ($u === '' || strlen($u) > 200 || !filter_var($u, FILTER_VALIDATE_URL)
        || !in_array($p['scheme'] ?? '', $local ? ['http', 'https'] : ['https'], true)
        || isset($p['user']) || isset($p['query']) || isset($p['fragment'])) {
        throw new RuntimeException('La URL debe ser https:// (p. ej. https://omr.midominio.com). El token no puede viajar sin cifrar.');
    }
    return $u;
}

/** '' = conservar el token guardado. */
function lector_token($t): string
{
    $t = trim((string) $t);
    if ($t !== '' && !preg_match('/^omr_[A-Za-z0-9_\-]{20,120}$/', $t)) {
        throw new RuntimeException('El token no tiene el formato esperado (empieza con "omr_").');
    }
    return $t;
}

function lector_plantilla($p): string
{
    $p = (string) $p;
    if (!preg_match('/^[a-z0-9_-]{1,40}$/', $p)) throw new RuntimeException('Elige la hoja por defecto.');
    return $p;
}

try {
    switch ($accion) {

        case 'probar':
            $url   = lector_url(post('url'));
            $token = lector_token(post('token')) ?: omr_token();
            if ($token === '') throw new RuntimeException('Escribe el token para probar la conexión.');
            $lista = omr_peticion('GET', '/plantillas', null, 20, $url, $token);
            Respuesta::ok(['plantillas' => $lista],
                'Conexión correcta: el lector tiene ' . count($lista) . ' hoja(s) registrada(s).');
            break;

        case 'guardar':
            $url       = lector_url(post('url'));
            $nuevo     = lector_token(post('token'));
            $token     = $nuevo ?: omr_token();
            $plantilla = lector_plantilla(post('plantilla'));
            if ($token === '') throw new RuntimeException('Escribe el token del lector.');

            // Solo se guarda una conexión que funciona de verdad
            $lista   = omr_peticion('GET', '/plantillas', null, 20, $url, $token);
            $nombres = array_column($lista, 'nombre');
            if (!in_array($plantilla, $nombres, true)) {
                throw new RuntimeException('La hoja "' . $plantilla . '" no existe en el lector. Disponibles: '
                                           . (implode(', ', $nombres) ?: 'ninguna'));
            }

            Db::iniciar();
            try {
                Ajustes::guardar('omr_url', $url);
                Ajustes::guardar('omr_plantilla', $plantilla);
                if ($nuevo !== '') Ajustes::guardar('omr_token', $nuevo, true);   // cifrado
                Db::confirmar();
            } catch (Throwable $e) {
                Db::revertir();
                throw $e;
            }
            unset($_SESSION['_omr_plantillas']);
            $detalle = "URL $url · hoja $plantilla" . ($nuevo !== '' ? ' · token nuevo ****' . substr($nuevo, -4) : '');
            bitacora('lector', 'guardar', $detalle);
            registrar('Lector de fichas configurado: ' . $detalle, 'WARN');
            Respuesta::ok(lector_estado(), 'Configuración guardada. Ya funciona para todas las academias.');
            break;

        case 'quitar':
            Ajustes::borrar('omr_token');
            unset($_SESSION['_omr_plantillas']);
            bitacora('lector', 'quitar', 'Token del lector eliminado');
            registrar('Token del lector de fichas eliminado', 'WARN');
            Respuesta::ok(lector_estado(), 'Token eliminado del sistema.');
            break;

        case '':
            Vista::render('lector', ['estado' => lector_estado()], 'Lector de fichas');
            break;

        default:
            Respuesta::error('Acción no válida: ' . $accion, 400);
    }
} catch (RuntimeException $e) {
    Respuesta::error($e->getMessage(), 422);
}
