<?php
/**
 * Módulo: Precios.
 * Único lugar donde se configuran las cuotas que cobra la academia
 * (estatal / particular / libre). Los alumnos y, más adelante, los
 * ingresos leen estos montos.
 */
defined('APP_NOMBRE') or exit;

require_once __DIR__ . '/precios_modelo.php';

try {
    switch ($accion) {

        case 'obtener':
            Respuesta::ok(['precios' => precios_obtener(), 'resumen' => precios_resumen()]);
            break;

        case 'guardar':
            [$errores, $datos] = precios_validar($_POST);
            if ($errores) Respuesta::error(implode(' ', $errores), 422);

            $antes = precios_obtener();
            precios_guardar($datos);

            $cambio = sprintf(
                'estatal %s->%s, particular %s->%s, libre %s->%s',
                $antes['estatal'],    $datos['tarifa_estatal'],
                $antes['particular'], $datos['tarifa_particular'],
                $antes['libre'],      $datos['tarifa_libre']
            );
            bitacora('precios', 'actualizar', $cambio);
            registrar('Precios actualizados: ' . $cambio);

            Respuesta::ok(
                ['precios' => precios_obtener(), 'resumen' => precios_resumen()],
                'Precios actualizados.'
            );
            break;

        case '':
            Contexto::exigirAcademia();
            Vista::render('precios', [
                'tipos'   => alumnos_tipos(),
                'precios' => precios_obtener(),
                'resumen' => precios_resumen(),
                'puedeEditar' => Auth::esRol('superadmin', 'admin'),
            ], 'Precios · ' . academia_nombre());
            break;

        default:
            Respuesta::error('Acción no válida: ' . $accion, 400);
    }
} catch (RuntimeException $e) {
    Respuesta::error($e->getMessage(), 409);
}
