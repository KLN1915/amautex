<?php
/**
 * Módulo: Usuarios (CRUD completo, siempre dentro de una academia).
 * Sin ?accion  -> renderiza la vista.
 * Con ?accion  -> responde JSON (lo consume assets/js/app.js).
 */
defined('APP_NOMBRE') or exit;

require_once __DIR__ . '/usuarios_modelo.php';

try {
    switch ($accion) {

        case 'listar':
            $r = usuarios_listar(Seguridad::texto(get('q', ''), 60), max(1, (int) get('pagina', 1)));
            Respuesta::ok($r);
            break;

        case 'obtener':
            $u = usuarios_obtener((int) get('id'));
            $u ? Respuesta::ok($u) : Respuesta::error('Usuario no encontrado.', 404);
            break;

        case 'guardar':
            $id = (int) post('id', 0);
            [$errores, $datos, $clave] = usuarios_validar($_POST, $id);
            if ($errores) {
                Respuesta::error(implode(' ', $errores), 422);
            }
            if ($id > 0) {
                usuarios_actualizar($id, $datos, $clave);
                bitacora('usuarios', 'actualizar', 'Usuario #' . $id);
                Respuesta::ok(['id' => $id], 'Usuario actualizado correctamente.');
            }
            $nuevo = usuarios_crear($datos, $clave);
            bitacora('usuarios', 'crear', 'Usuario #' . $nuevo . ' ' . $datos['usuario']);
            Respuesta::ok(['id' => $nuevo], 'Usuario creado correctamente.');
            break;

        case 'estado':
            $id = (int) post('id');
            if ($id === Auth::id()) Respuesta::error('No puedes desactivar tu propio usuario.', 409);
            usuarios_cambiar_estado($id, (int) post('activo'));
            Respuesta::ok(null, 'Estado actualizado.');
            break;

        case 'eliminar':
            $id = (int) post('id');
            if ($id === Auth::id()) {
                Respuesta::error('No puedes eliminar tu propio usuario.', 409);
            }
            usuarios_eliminar($id);
            bitacora('usuarios', 'eliminar', 'Usuario #' . $id);
            registrar('Usuario eliminado #' . $id, 'WARN');
            Respuesta::ok(null, 'Usuario eliminado.');
            break;

        case '':
            $academiaId = academia_id();
            Vista::render('usuarios', [
                'roles' => usuarios_roles_permitidos(),
                'cupo'  => $academiaId ? usuarios_cupo($academiaId) : null,
            ], 'Usuarios · ' . academia_nombre());
            break;

        default:
            Respuesta::error('Acción no válida: ' . $accion, 400);
    }
} catch (RuntimeException $e) {
    // Errores de negocio previstos (límite de plan, usuario de otra academia…)
    Respuesta::error($e->getMessage(), 409);
}
