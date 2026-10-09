<?php
/**
 * Módulo: Exámenes con fichas ópticas.
 *
 *   1. Crear el examen (nombre, nivel/grado, hoja del lector).
 *   2. Configurar la clave: se escanea una hoja (en blanco o marcada), el sistema
 *      propone las respuestas y se confirma o corrige cada una.
 *   3. Escanear alumnos: código del alumno -> foto de su ficha -> revisar -> Guardar.
 *   4. Resultados del examen.
 */
defined('APP_NOMBRE') or exit;

require_once __DIR__ . '/examenes_modelo.php';

/** Crean, configuran y escanean. `consulta` solo mira los resultados. */
const EXAMENES_ROLES_ESCRITURA = ['superadmin', 'admin', 'operador'];
/** Borrar un examen (y todas sus fichas) es decisión del administrador. */
const EXAMENES_ROLES_BORRAR = ['superadmin', 'admin'];

try {
    switch ($accion) {

        // ---------------------------------------------------------------- exámenes
        case 'listar':
            Respuesta::ok(examenes_listar());
            break;

        case 'obtener':
            Respuesta::ok(examenes_exigir((int) get('id')));
            break;

        case 'plantillas':
            Respuesta::ok(omr_plantillas());
            break;

        case 'guardar':
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            $id = (int) post('id', 0);
            $actual = $id ? examenes_exigir($id) : null;
            [$errores, $datos] = examenes_validar($_POST, $actual);
            if ($errores) Respuesta::error(implode(' ', $errores), 422);
            if ($id) {
                examenes_actualizar($id, $datos);
                bitacora('examenes', 'actualizar', 'Examen #' . $id . ' ' . $datos['nombre']);
                Respuesta::ok(['id' => $id], array_key_exists('clave', $datos) && $actual['clave']
                    ? 'Examen actualizado. Cambió la hoja: vuelve a configurar las respuestas.'
                    : 'Examen actualizado.');
            }
            $nuevo = examenes_crear($datos);
            bitacora('examenes', 'crear', 'Examen #' . $nuevo . ' ' . $datos['nombre']);
            Respuesta::ok(['id' => $nuevo], 'Examen creado. Ahora configura sus respuestas.');
            break;

        case 'eliminar':
            Auth::exigirRol(...EXAMENES_ROLES_BORRAR);
            $e = examenes_exigir((int) post('id'));
            examenes_eliminar((int) $e['id']);
            bitacora('examenes', 'eliminar', 'Examen #' . $e['id'] . ' ' . $e['nombre']);
            registrar('Examen eliminado con sus fichas: ' . $e['nombre'], 'WARN');
            Respuesta::ok(null, 'Examen eliminado junto con sus fichas.');
            break;

        // ---------------------------------------------------------------- clave
        case 'leer_clave':
            // Escanea una hoja (en blanco o marcada) para PROPONER la clave
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            $e = examenes_exigir((int) post('id'));
            $foto = omr_validar_foto($_FILES['imagen'] ?? null);
            $d = omr_peticion('POST', '/calificar', [
                'imagen'    => new CURLFile($foto['tmp_name'], $foto['mime'], 'clave.jpg'),
                'plantilla' => $e['plantilla'],
            ]);
            examenes_verificar_hoja($e, $d);
            $propuesta = array_map(fn($p) => [
                'n'         => (int) $p['n'],
                'respuesta' => in_array($p['estado'], ['ok', 'borron'], true) ? substr((string) $p['respuesta'], 0, 1) : '',
                'estado'    => $p['estado'],
                'revisar'   => (bool) $p['revisar'],
            ], $d['detalle']);
            $marcadas = count(array_filter($propuesta, fn($p) => $p['respuesta'] !== ''));
            Respuesta::ok(['propuesta' => $propuesta, 'marcadas' => $marcadas],
                $marcadas ? "Se propusieron $marcadas respuestas. Revisa y corrige las que falten."
                          : 'Hoja en blanco: elige la respuesta correcta de cada pregunta.');
            break;

        case 'guardar_clave':
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            $e = examenes_exigir((int) post('id'));
            $clave = examenes_validar_clave($e, post('clave', ''));
            $recalculadas = examenes_guardar_clave($e, $clave);
            bitacora('examenes', 'clave', 'Examen #' . $e['id'] . ' clave ' . ($e['clave'] ? 'cambiada' : 'configurada')
                     . ($recalculadas ? " ($recalculadas fichas recalculadas)" : ''));
            Respuesta::ok(['recalculadas' => $recalculadas], $recalculadas
                ? "Clave guardada. Se recalcularon las notas de $recalculadas ficha(s)."
                : 'Clave guardada. Ya puedes escanear las fichas de los alumnos.');
            break;

        // ---------------------------------------------------------------- escanear alumnos
        case 'alumno':
            $e = examenes_exigir((int) get('examen'));
            $a = examenes_alumno_por_codigo($e, (string) get('codigo', ''));
            $a ? Respuesta::ok($a) : Respuesta::error('No hay ningún alumno con el código «'
                . Seguridad::texto(get('codigo', ''), 20) . '» en esta academia.', 404);
            break;

        case 'leer_alumno':
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            $e = examenes_exigir((int) post('examen'));
            if (!$e['clave']) throw new RuntimeException('Primero configura las respuestas correctas del examen.');
            $alumno = examenes_alumno_exigir((int) post('alumno'));
            $foto = omr_validar_foto($_FILES['imagen'] ?? null);
            examenes_tmp_limpiar();

            $d = omr_peticion('POST', '/calificar', [
                'imagen'    => new CURLFile($foto['tmp_name'], $foto['mime'], $alumno['codigo'] . '.jpg'),
                'plantilla' => $e['plantilla'],
                'clave'     => $e['clave'],          // solo para pintar la foto corregida
                'anotada'   => 'true',
            ]);
            examenes_verificar_hoja($e, $d);

            $respuestas = examenes_respuestas_api($d['detalle']);
            $conteo = examenes_calificar($e['clave'], $respuestas);
            $escaneo = examenes_escaneo_poner([
                'academia_id' => Contexto::academiaId(),
                'examen_id'   => (int) $e['id'],
                'alumno_id'   => (int) $alumno['id'],
                'respuestas'  => $respuestas,
                'revisar'     => array_map('intval', $d['revisar']),
                'tmp'         => examenes_tmp_imagen($d['imagen_corregida'] ?? null),
            ]);
            Respuesta::ok([
                'escaneo'    => $escaneo,
                'conteo'     => $conteo,
                'respuestas' => str_split($respuestas),
                'clave'      => str_split($e['clave']),
                'revisar'    => $d['revisar'],
                'aviso'      => $d['aviso'],
                'imagen'     => $d['imagen_corregida'] ?? null,
            ], 'Ficha leída. Revisa y pulsa Guardar.');
            break;

        case 'guardar_resultado':
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            // la entrega se valida ANTES de consumir el escaneo: si falta la hora,
            // se corrige y se vuelve a guardar sin tener que escanear otra vez
            $entrega = examenes_validar_entrega(post('fecha', ''), post('hora', ''));
            $esc = examenes_escaneo_tomar((string) post('escaneo'));
            $id = examenes_resultado_guardar($esc, $entrega);
            $r = examenes_resultado_obtener($id);
            $p = examenes_puntaje($r);
            bitacora('examenes', 'resultado', 'Examen #' . $esc['examen_id'] . ' alumno #' . $esc['alumno_id']
                     . ' ' . $r['correctas'] . '/' . $r['total'] . ' · ' . $p['puntaje'] . ' pts · entregó ' . $entrega);
            Respuesta::ok(['id' => $id, 'correctas' => (int) $r['correctas'], 'total' => (int) $r['total'],
                           'puntaje' => $p['puntaje'], 'maximo' => $p['maximo'],
                           'escaneados' => examenes_contar_resultados((int) $esc['examen_id'])],
                          'Resultado guardado.');
            break;

        // ---------------------------------------------------------------- reglas de puntaje
        case 'guardar_reglas':
            Auth::exigirRol(...EXAMENES_ROLES_BORRAR);          // admin de la academia
            $reglas = examenes_validar_reglas($_POST);
            examenes_guardar_reglas($reglas);
            bitacora('examenes', 'reglas', sprintf('Puntaje: correcta %+g, incorrecta %+g, en blanco %+g',
                     $reglas['correcta'], $reglas['incorrecta'], $reglas['blanco']));
            Respuesta::ok($reglas, 'Reglas de puntaje guardadas. Todos los resultados ya usan estos puntos.');
            break;

        // ---------------------------------------------------------------- resultados
        case 'resultados_datos':
            $e = examenes_exigir((int) get('id'));
            Respuesta::ok(['examen' => $e, 'reglas' => examenes_reglas(), 'filas' => examenes_resultados((int) $e['id'])]);
            break;

        case 'hoja':
            // Foto corregida: solo se entrega a usuarios de la misma academia
            $r = examenes_resultado_obtener((int) get('id'));
            $f = $r && $r['imagen'] && !str_contains($r['imagen'], '..') ? RUTA_UPLOADS . '/' . $r['imagen'] : null;
            if (!$f || !is_file($f)) Vista::error(404, 'La ficha no existe.');
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($f));
            header('Cache-Control: private, max-age=3600');
            header('X-Content-Type-Options: nosniff');
            readfile($f);
            exit;

        case 'eliminar_resultado':
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            examenes_resultado_eliminar((int) post('id'));
            bitacora('examenes', 'eliminar_resultado', 'Resultado #' . (int) post('id'));
            Respuesta::ok(null, 'Resultado eliminado.');
            break;

        case 'exportar':
            $e = examenes_exigir((int) get('id'));
            $filas = examenes_resultados((int) $e['id']);
            $nombre = preg_replace('/[^A-Za-z0-9_-]+/', '_', $e['nombre']) ?: 'examen';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="resultados_' . $nombre . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                               // BOM: Excel ve las tildes
            fputcsv($out, array_merge(['Puesto', 'Código', 'Alumno', 'Grado', 'Buenas', 'Malas', 'En blanco', 'Puntaje',
                      'Máximo', 'Entregó', 'Revisar'],
                      array_map(fn($i) => 'P' . $i, range(1, (int) $e['preguntas']))), ';');
            foreach ($filas as $f) {
                fputcsv($out, array_merge([$f['puesto'], $f['codigo'], $f['alumno'], $f['grado_texto'], $f['correctas'],
                          $f['incorrectas'], $f['blancos'], $f['puntaje'], $f['maximo'],
                          $f['entregado_en'] ? date('d/m/Y H:i', strtotime($f['entregado_en'])) : '',
                          implode(' ', $f['revisar'])],
                          // una columna por pregunta: en blanco = celda vacía, doble marca = X
                          array_map(fn($r) => $r === '_' ? '' : ($r === '*' ? 'X' : $r), str_split($f['respuestas']))), ';');
            }
            fclose($out);
            exit;

        // ---------------------------------------------------------------- pantallas
        case 'clave':
            Contexto::exigirAcademia();
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            $e = examenes_obtener((int) get('id')) ?? Vista::error(404, 'El examen no existe.');
            Vista::render('examen_clave', ['examen' => $e, 'letras' => examenes_letras($e)],
                          'Respuestas · ' . $e['nombre']);
            break;

        case 'escanear':
            Contexto::exigirAcademia();
            Auth::exigirRol(...EXAMENES_ROLES_ESCRITURA);
            $e = examenes_obtener((int) get('id')) ?? Vista::error(404, 'El examen no existe.');
            if (!$e['clave']) {
                flash('Antes de escanear alumnos, configura las respuestas correctas del examen.', 'warning');
                redirigir(modulo('examenes', ['accion' => 'clave', 'id' => $e['id']]));
            }
            Vista::render('examen_escanear', ['examen' => $e, 'escaneados' => examenes_contar_resultados((int) $e['id'])],
                          'Escanear · ' . $e['nombre']);
            break;

        case 'resultados':
            Contexto::exigirAcademia();
            $e = examenes_obtener((int) get('id')) ?? Vista::error(404, 'El examen no existe.');
            Vista::render('examen_resultados', ['examen' => $e, 'puedeEditar' => Auth::esRol(...EXAMENES_ROLES_ESCRITURA)],
                          'Resultados · ' . $e['nombre']);
            break;

        case '':
            Contexto::exigirAcademia();
            Vista::render('examenes', [
                'niveles'     => alumnos_niveles(),
                'reglas'      => examenes_reglas(),
                'puedeEditar' => Auth::esRol(...EXAMENES_ROLES_ESCRITURA),
                'puedeBorrar' => Auth::esRol(...EXAMENES_ROLES_BORRAR),
                'lectorListo' => omr_configurado(),
                'plantilla'   => omr_plantilla_defecto(),
            ], 'Exámenes · ' . academia_nombre());
            break;

        default:
            Respuesta::error('Acción no válida: ' . $accion, 400);
    }
} catch (RuntimeException $e) {
    Respuesta::error($e->getMessage(), 422);
}
