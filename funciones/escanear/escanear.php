<?php
/**
 * Módulo: Escanear fichas ópticas.
 * La cámara (o una foto del celular) captura la ficha, Amautex la reenvía al
 * lector OMR del VPS por su API y devuelve las respuestas y la nota.
 */
defined('APP_NOMBRE') or exit;

require_once __DIR__ . '/escanear_modelo.php';

try {
    switch ($accion) {

        case 'plantillas':
            Respuesta::ok(omr_plantillas());
            break;

        case 'calificar':
            $foto      = omr_validar_foto($_FILES['imagen'] ?? null);
            $clave     = omr_limpiar_clave(post('clave', ''));
            $plantilla = omr_limpiar_plantilla(post('plantilla', ''));

            $d = omr_calificar($foto, $clave, $plantilla);

            $nota = isset($d['nota']['correctas']) ? $d['nota']['correctas'] . '/' . $d['nota']['total'] : 'sin clave';
            bitacora('escanear', 'calificar', 'Ficha ' . $nota . ' · ' . ($d['modo'] ?? ''));
            Respuesta::ok($d, 'Ficha leída.');
            break;

        case '':
            Contexto::exigirAcademia();
            Vista::render('escanear', [
                'configurado' => omr_configurado(),
                'plantilla'   => omr_plantilla_defecto(),
            ], 'Escanear fichas · ' . academia_nombre());
            break;

        default:
            Respuesta::error('Acción no válida: ' . $accion, 400);
    }
} catch (RuntimeException $e) {
    Respuesta::error($e->getMessage(), 422);
}
