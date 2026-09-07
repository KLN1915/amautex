<?php
/**
 * Módulo: Academias (solo superadmin).
 * Alta de clientes que alquilan el sistema, control del plan,
 * suspensión / renovación y gestión de credenciales de acceso.
 */
defined('APP_NOMBRE') or exit;

require_once __DIR__ . '/academias_modelo.php';

switch ($accion) {

    case 'listar':
        Respuesta::ok(academias_listar(
            Seguridad::texto(get('q', ''), 60),
            Seguridad::texto(get('estado', ''), 15),
            max(1, (int) get('pagina', 1))
        ));
        break;

    case 'obtener':
        $a = academias_obtener((int) get('id'));
        $a ? Respuesta::ok($a) : Respuesta::error('Academia no encontrada.', 404);
        break;

    case 'guardar':
        $id = (int) post('id', 0);
        [$errores, $datos] = academias_validar($_POST, $id);

        if ($id > 0) {
            if ($errores) Respuesta::error(implode(' ', $errores), 422);
            academias_actualizar($id, $datos);
            bitacora('academias', 'actualizar', 'Academia #' . $id . ' ' . $datos['nombre']);
            Respuesta::ok(['id' => $id], 'Academia actualizada.');
        }

        // Alta: además de la academia se crea su usuario administrador
        $admin = [
            'nombre'  => Seguridad::texto(post('admin_nombre', ''), 100),
            'usuario' => Seguridad::texto(post('admin_usuario', ''), 50),
            'correo'  => Seguridad::correo(post('admin_correo', '')),
            'clave'   => (string) post('admin_clave', ''),
        ];
        if ($admin['clave'] === '') $admin['clave'] = academias_clave_aleatoria();

        if ($admin['nombre'] === '')  $errores[] = 'El nombre del administrador es obligatorio.';
        if ($admin['usuario'] === '') $errores[] = 'El usuario del administrador es obligatorio.';
        if (strlen($admin['clave']) < 6) $errores[] = 'La contraseña del administrador debe tener al menos 6 caracteres.';
        if ($admin['usuario'] !== '' && Db::valor('SELECT COUNT(*) FROM usuarios WHERE usuario = :u', ['u' => $admin['usuario']])) {
            $errores[] = 'El usuario "' . $admin['usuario'] . '" ya existe en el sistema. Elige otro.';
        }
        if ($errores) Respuesta::error(implode(' ', $errores), 422);

        $nueva = academias_crear($datos, $admin);
        bitacora('academias', 'crear', 'Academia #' . $nueva . ' ' . $datos['nombre']);
        registrar('Academia creada: ' . $datos['nombre'] . ' (admin: ' . $admin['usuario'] . ')');

        Respuesta::ok(
            ['id' => $nueva, 'usuario' => $admin['usuario'], 'clave' => $admin['clave']],
            'Academia creada. Entrega estas credenciales al cliente.'
        );
        break;

    case 'estado':
        $id     = (int) post('id');
        $estado = post('estado') === 'suspendida' ? 'suspendida' : 'activa';
        academias_cambiar_estado($id, $estado);
        bitacora('academias', 'estado', 'Academia #' . $id . ' -> ' . $estado);
        Respuesta::ok(null, $estado === 'activa' ? 'Academia activada.' : 'Academia suspendida.');
        break;

    case 'renovar':
        $id    = (int) post('id');
        $meses = max(1, min(36, (int) post('meses', 1)));
        $nuevo = academias_renovar($id, $meses);
        if (!$nuevo) Respuesta::error('Academia no encontrada.', 404);
        bitacora('academias', 'renovar', 'Academia #' . $id . ' hasta ' . $nuevo);
        Respuesta::ok(['fin_plan' => $nuevo], 'Plan renovado hasta el ' . fecha($nuevo) . '.');
        break;

    case 'eliminar':
        $id = (int) post('id');
        $a  = academias_obtener($id);
        if (!$a) Respuesta::error('Academia no encontrada.', 404);
        academias_eliminar($id);
        if (($_SESSION['academia_ctx'] ?? null) === $id) $_SESSION['academia_ctx'] = null;
        registrar('Academia eliminada: ' . $a['nombre'], 'WARN');
        Respuesta::ok(null, 'Academia eliminada junto con sus datos.');
        break;

    // ---------- Credenciales de acceso ----------
    case 'credenciales':
        $id = (int) get('id');
        $a  = academias_obtener($id);
        if (!$a) Respuesta::error('Academia no encontrada.', 404);
        Respuesta::ok(['academia' => $a, 'usuarios' => academias_credenciales($id)]);
        break;

    case 'resetear_clave':
        $academiaId = (int) post('academia_id');
        $usuarioId  = (int) post('usuario_id');
        $clave = academias_resetear_clave($academiaId, $usuarioId);
        if ($clave === null) Respuesta::error('El usuario no pertenece a esa academia.', 404);
        bitacora('academias', 'resetear_clave', 'Usuario #' . $usuarioId . ' de academia #' . $academiaId);
        registrar('Clave restablecida al usuario #' . $usuarioId, 'WARN');
        Respuesta::ok(['clave' => $clave], 'Contraseña restablecida.');
        break;

    // ---------- Entrar al contexto de una academia ----------
    case 'acceder':
        $id = post('id');
        Contexto::cambiar($id === '' || $id === null ? null : (int) $id);
        Respuesta::ok(['academia' => Contexto::nombre()], 'Contexto cambiado.');
        break;

    case '':
        Vista::render('academias', [], 'Academias');
        break;

    default:
        Respuesta::error('Acción no válida: ' . $accion, 400);
}
