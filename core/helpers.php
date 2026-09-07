<?php
/**
 * Funciones cortas de uso diario en vistas y módulos.
 */

/** Escape rápido para imprimir en HTML: <?= e($fila['nombre']) ?> */
function e($v): string { return Seguridad::e($v); }

/** Lee de $_POST con valor por defecto. */
function post(string $clave, $def = null) { return $_POST[$clave] ?? $def; }

/** Lee de $_GET con valor por defecto. */
function get(string $clave, $def = null) { return $_GET[$clave] ?? $def; }

/** URL absoluta del sistema. */
function url(string $ruta = ''): string { return BASE_URL . ltrim($ruta, '/'); }

/** URL de un módulo: modulo('usuarios') -> index.php?p=usuarios */
function modulo(string $p, array $extra = []): string
{
    $q = array_merge(['p' => $p], $extra);
    return BASE_URL . 'index.php?' . http_build_query($q);
}

/** URL de un archivo de assets con cache-busting. */
function asset(string $ruta): string
{
    $f = RAIZ . '/assets/' . ltrim($ruta, '/');
    $v = is_file($f) ? filemtime($f) : APP_ENTORNO;
    return BASE_URL . 'assets/' . ltrim($ruta, '/') . '?v=' . $v;
}

/** Redirige y corta. */
function redirigir(string $destino): void { header('Location: ' . $destino); exit; }

/** Mensaje flash para la siguiente carga de página. */
function flash(string $mensaje = null, string $tipo = 'success')
{
    if ($mensaje !== null) { $_SESSION['_flash'][] = ['tipo' => $tipo, 'texto' => $mensaje]; return null; }
    $m = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $m;
}

/**
 * Roles que solo miran. El rol `consulta` entra a los módulos de su academia
 * pero no crea, edita ni borra: se usa para esconder los botones en la vista.
 * La barrera de verdad va en el controlador, con Auth::exigirRol().
 */
function solo_lectura(): bool { return Auth::esRol('consulta'); }

function puede_editar(): bool { return !solo_lectura(); }

/** Formatos de uso común. */
function fecha(?string $f, string $formato = 'd/m/Y'): string
{
    if (empty($f) || $f === '0000-00-00') return '';
    $ts = strtotime($f);
    return $ts ? date($formato, $ts) : '';
}
function moneda($n, string $simbolo = 'S/ '): string { return $simbolo . number_format((float) $n, 2); }

/** Normaliza una fecha de formulario a formato SQL (o NULL si viene vacía). */
function fecha_sql($valor): ?string
{
    $valor = trim((string) $valor);
    if ($valor === '') return null;
    $ts = strtotime($valor);
    return $ts ? date('Y-m-d', $ts) : null;
}

/** Marca "active" el ítem del menú actual. */
function activo(string $p): string { return (get('p', 'dashboard') === $p) ? 'active' : ''; }

/** Registro simple en logs/app.log */
function registrar(string $mensaje, string $nivel = 'INFO'): void
{
    $linea = sprintf(
        "[%s] %s aca=%s u=%s %s\n",
        date('Y-m-d H:i:s'),
        $nivel,
        Contexto::academiaId() ?? '-',
        Auth::usuario('usuario') ?? '-',
        $mensaje
    );
    @file_put_contents(RUTA_LOGS . '/app.log', $linea, FILE_APPEND);
}

/** Registro en la tabla bitacora (auditoría por academia). */
function bitacora(string $modulo, string $accion, string $detalle = ''): void
{
    try {
        Db::insertar('bitacora', [
            'academia_id' => Contexto::academiaId(),
            'usuario_id'  => Auth::logueado() ? Auth::id() : null,
            'modulo'      => $modulo,
            'accion'      => $accion,
            'detalle'     => $detalle,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('bitacora: ' . $e->getMessage());
    }
}

// ---------- Atajos de multi-academia ----------

/** Academia en la que se está trabajando (NULL = superadmin en vista global). */
function academia_id(): ?int { return Contexto::academiaId(); }

/** ¿El usuario logueado es el dueño del sistema? */
function es_super(): bool { return Auth::esSuper(); }

/** Nombre de la academia actual, para títulos y cabeceras. */
function academia_nombre(): string { return Contexto::nombre(); }
