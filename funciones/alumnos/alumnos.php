<?php
/**
 * Módulo: Alumnos.
 * Registro de alumnos de la academia: código, apellidos y nombres,
 * institución educativa, nivel y grado.
 */
defined('APP_NOMBRE') or exit;

require_once __DIR__ . '/alumnos_modelo.php';

/** Roles que pueden modificar alumnos. `consulta` entra, pero solo mira. */
const ALUMNOS_ROLES_ESCRITURA = ['superadmin', 'admin', 'operador'];

try {
    switch ($accion) {

        case 'listar':
            Respuesta::ok(alumnos_listar([
                'q'       => Seguridad::texto(get('q', ''), 60),
                'nivel'   => Seguridad::texto(get('nivel', ''), 15),
                'grado'   => Seguridad::texto(get('grado', ''), 2),
                'tipo'    => Seguridad::texto(get('tipo', ''), 15),
                'colegio' => get('colegio', ''),
                'activo'  => get('activo', ''),
            ], max(1, (int) get('pagina', 1))));
            break;

        case 'obtener':
            $a = alumnos_obtener((int) get('id'));
            $a ? Respuesta::ok($a) : Respuesta::error('Alumno no encontrado.', 404);
            break;

        case 'siguiente_codigo':
            Respuesta::ok(['codigo' => alumnos_siguiente_codigo()]);
            break;

        case 'colegios':
            // Alimenta el combobox: los colegios del tipo elegido
            Respuesta::ok(colegios_listar(Seguridad::texto(get('tipo', ''), 15)));
            break;

        case 'panel':
            // Paneles de abajo, contador del plan y catálogo de colegios:
            // lo que cambia al guardar o borrar y antes obligaba a recargar.
            Respuesta::ok(alumnos_panel());
            break;

        case 'guardar':
            Auth::exigirRol(...ALUMNOS_ROLES_ESCRITURA);
            $id = (int) post('id', 0);
            [$errores, $datos] = alumnos_validar($_POST, $id);
            if ($errores) Respuesta::error(implode(' ', $errores), 422);

            if ($id > 0) {
                alumnos_actualizar($id, $datos);
                bitacora('alumnos', 'actualizar', 'Alumno #' . $id);
                Respuesta::ok(['id' => $id], 'Alumno actualizado.');
            }
            $nuevo = alumnos_crear($datos);
            bitacora('alumnos', 'crear', 'Alumno #' . $nuevo . ' ' . $datos['apellido_paterno']);
            Respuesta::ok(['id' => $nuevo], 'Alumno registrado.');
            break;

        case 'estado':
            Auth::exigirRol(...ALUMNOS_ROLES_ESCRITURA);
            alumnos_cambiar_estado((int) post('id'), (int) post('activo'));
            Respuesta::ok(null, 'Estado actualizado.');
            break;

        case 'eliminar':
            Auth::exigirRol(...ALUMNOS_ROLES_ESCRITURA);
            $id = (int) post('id');
            alumnos_eliminar($id);
            bitacora('alumnos', 'eliminar', 'Alumno #' . $id);
            registrar('Alumno eliminado #' . $id, 'WARN');
            Respuesta::ok(null, 'Alumno eliminado.');
            break;

        case '':
            $academiaId = Contexto::exigirAcademia();
            Vista::render('alumnos', [
                // Tom Select: combobox de colegios con buscador
                'estilos'      => ['https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css'],
                'niveles'      => alumnos_niveles(),
                'tipos'        => alumnos_tipos(),
                'tarifas'      => alumnos_tarifas(),
                'colegios'     => colegios_listar(),
                'cupo'         => alumnos_cupo($academiaId),
                'resumen'      => alumnos_resumen(),
                'resumenTipo'  => alumnos_resumen_tipo(),
            ], 'Alumnos · ' . academia_nombre());
            break;

        default:
            Respuesta::error('Acción no válida: ' . $accion, 400);
    }
} catch (RuntimeException $e) {
    Respuesta::error($e->getMessage(), 409);
}
