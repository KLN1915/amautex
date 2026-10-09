<?php
/**
 * Consultas del módulo Exámenes.
 * Todo pasa por Contexto::filtro(): una academia nunca ve exámenes, alumnos ni
 * fichas de otra, aunque cambie los ids en la petición.
 */
defined('APP_NOMBRE') or exit;

require_once RUTA_FUNC . '/alumnos/alumnos_modelo.php';      // alumnos_niveles(), alumnos_grado_texto()
require_once RUTA_FUNC . '/escanear/escanear_modelo.php';    // cliente de la API del lector

const EXAMEN_BLANCO   = '_';
const EXAMEN_MULTIPLE = '*';
const EXAMEN_ANULADA  = '-';

// ================================================================ exámenes

function examenes_listar(): array
{
    [$where, $params] = Contexto::filtro('e');
    $filas = Db::todos(
        "SELECT e.id, e.nombre, e.nivel, e.grado, e.fecha, e.plantilla, e.preguntas, e.opciones,
                e.clave IS NOT NULL AS tiene_clave, e.creado_en,
                (SELECT COUNT(*) FROM examen_resultados r WHERE r.examen_id = e.id) AS resultados
           FROM examenes e
          WHERE $where
       ORDER BY COALESCE(e.fecha, DATE(e.creado_en)) DESC, e.id DESC",
        $params
    );
    foreach ($filas as &$f) {
        $f['grado_texto'] = examenes_grado_texto($f['nivel'], $f['grado']);
        $f['tiene_clave'] = (bool) $f['tiene_clave'];
    }
    return $filas;
}

/** El examen SOLO si es de la academia actual. */
function examenes_obtener(int $id): ?array
{
    [$where, $params] = Contexto::filtro();
    $params['id'] = $id;
    $e = Db::uno("SELECT * FROM examenes WHERE id = :id AND $where", $params);
    if ($e) $e['grado_texto'] = examenes_grado_texto($e['nivel'], $e['grado']);
    return $e;
}

function examenes_exigir(int $id): array
{
    $e = examenes_obtener($id);
    if (!$e) throw new RuntimeException('El examen no existe o no pertenece a esta academia.');
    return $e;
}

function examenes_grado_texto(string $nivel, $grado): string
{
    if ($grado === null || $grado === '') {
        return 'Todo ' . mb_strtolower(alumnos_niveles()[$nivel]['titulo'] ?? $nivel);
    }
    return alumnos_grado_texto($nivel, $grado);
}

function examenes_contar_resultados(int $examenId): int
{
    return (int) Db::valor('SELECT COUNT(*) FROM examen_resultados WHERE examen_id = :e', ['e' => $examenId]);
}

/** Valida el formulario del examen. Devuelve [errores, datos]. */
function examenes_validar(array $entrada, ?array $actual = null): array
{
    $errores = [];
    $niveles = alumnos_niveles();
    $nivel = (string) ($entrada['nivel'] ?? '');
    $grado = trim((string) ($entrada['grado'] ?? ''));
    $d = [
        'nombre'    => Seguridad::texto($entrada['nombre'] ?? '', 120),
        'nivel'     => isset($niveles[$nivel]) ? $nivel : '',
        'grado'     => $grado === '' ? null : (int) $grado,
        'fecha'     => fecha_sql($entrada['fecha'] ?? ''),
        'plantilla' => (string) ($entrada['plantilla'] ?? ''),
    ];
    if ($d['nombre'] === '') $errores[] = 'Ponle un nombre al examen.';
    if ($d['nivel'] === '')  $errores[] = 'Elige el nivel.';
    if ($d['nivel'] !== '' && $d['grado'] !== null && !isset($niveles[$d['nivel']]['grados'][$d['grado']])) {
        $errores[] = 'Ese grado no existe en ' . mb_strtolower($niveles[$d['nivel']]['titulo']) . '.';
    }

    // La hoja define cuántas preguntas y opciones tiene el examen
    $cambiaHoja = !$actual || $actual['plantilla'] !== $d['plantilla'];
    if ($cambiaHoja) {
        if ($actual && examenes_contar_resultados((int) $actual['id']) > 0) {
            $errores[] = 'No se puede cambiar la hoja: el examen ya tiene fichas escaneadas.';
        } else {
            $hoja = null;
            foreach (omr_plantillas() as $p) {
                if ($p['nombre'] === $d['plantilla']) $hoja = $p;
            }
            if (!$hoja) {
                $errores[] = 'Elige una hoja registrada en el lector de fichas.';
            } else {
                $d['preguntas'] = (int) $hoja['preguntas'];
                $d['opciones']  = (int) $hoja['opciones'];
                if ($actual) $d['clave'] = null;        // otra hoja: la clave anterior ya no sirve
            }
        }
    }
    return [$errores, $d];
}

function examenes_crear(array $datos): int
{
    $datos = Contexto::marcar($datos);
    $datos['creado_por'] = Auth::id();
    return Db::insertar('examenes', $datos);
}

function examenes_actualizar(int $id, array $datos): void
{
    examenes_exigir($id);
    Db::actualizar('examenes', $datos, 'id = :id', ['id' => $id]);
}

function examenes_eliminar(int $id): void
{
    $e = examenes_exigir($id);
    Db::eliminar('examenes', 'id = :id', ['id' => $id]);       // la FK borra los resultados
    examenes_borrar_carpeta(RUTA_UPLOADS . '/examenes/' . (int) $e['academia_id'] . '/' . $id);
}

function examenes_borrar_carpeta(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*') ?: [] as $f) is_file($f) && @unlink($f);
    @rmdir($dir);
}

// ================================================================ clave y calificación

/** Letras válidas para el examen: A..E (según sus opciones). */
function examenes_letras(array $examen): string
{
    return substr('ABCDEFGHIJ', 0, max(2, min(10, (int) $examen['opciones'])));
}

function examenes_validar_clave(array $examen, $clave): string
{
    $c = strtoupper(preg_replace('/\s+/', '', (string) $clave));
    $n = (int) $examen['preguntas'];
    if (strlen($c) !== $n) {
        throw new RuntimeException("La clave debe tener exactamente $n respuestas (tiene " . strlen($c) . ').');
    }
    $permitidas = examenes_letras($examen) . EXAMEN_ANULADA;
    if (strspn($c, $permitidas) !== $n) {
        throw new RuntimeException('Hay preguntas sin respuesta correcta elegida. Elige una alternativa o márcala como anulada.');
    }
    if (strspn($c, EXAMEN_ANULADA) === $n) {
        throw new RuntimeException('No puedes anular todas las preguntas.');
    }
    return $c;
}

/** Convierte el detalle de la API en un carácter por pregunta: letra, _ o *. */
function examenes_respuestas_api(array $detalle): string
{
    $s = '';
    foreach ($detalle as $p) {
        $s .= match ($p['estado'] ?? '') {
            'ok', 'borron' => substr((string) $p['respuesta'], 0, 1) ?: EXAMEN_BLANCO,
            'multiple'     => EXAMEN_MULTIPLE,
            default        => EXAMEN_BLANCO,
        };
    }
    return $s;
}

/**
 * La calificación se hace AQUÍ (no en el navegador ni en la API) a partir de las
 * respuestas guardadas: así las notas se recalculan si cambia la clave.
 */
function examenes_calificar(string $clave, string $respuestas): array
{
    $c = ['correctas' => 0, 'incorrectas' => 0, 'blancos' => 0, 'multiples' => 0, 'total' => 0, 'anuladas' => 0];
    $n = strlen($clave);
    for ($i = 0; $i < $n; $i++) {
        $k = $clave[$i];
        $r = $respuestas[$i] ?? EXAMEN_BLANCO;
        if ($k === EXAMEN_ANULADA) { $c['anuladas']++; continue; }
        $c['total']++;
        if ($r === EXAMEN_BLANCO)        $c['blancos']++;
        elseif ($r === EXAMEN_MULTIPLE)  { $c['multiples']++; $c['incorrectas']++; }
        elseif ($r === $k)               $c['correctas']++;
        else                             $c['incorrectas']++;
    }
    return $c + examenes_puntaje($c);
}

// ================================================================ reglas de puntaje (por academia)

/** Puntos por correcta / incorrecta / en blanco que usa la academia actual. */
function examenes_reglas(): array
{
    $a = Contexto::academia() ?? [];
    return [
        'correcta'   => (float) ($a['puntaje_correcta']   ?? 10),
        'incorrecta' => (float) ($a['puntaje_incorrecta'] ?? -2),
        'blanco'     => (float) ($a['puntaje_blanco']     ?? 0),
    ];
}

/**
 * Puntaje con las reglas vigentes. No se guarda: se calcula al vuelo a partir del
 * conteo, así cambiar las reglas actualiza todos los resultados de la academia.
 * La doble marca ya viene sumada en `incorrectas`.
 */
function examenes_puntaje(array $c, ?array $reglas = null): array
{
    $r = $reglas ?? examenes_reglas();
    $puntaje = $c['correctas'] * $r['correcta'] + $c['incorrectas'] * $r['incorrecta'] + $c['blancos'] * $r['blanco'];
    $maximo  = $c['total'] * $r['correcta'];
    return [
        'puntaje'    => round($puntaje, 2),
        'maximo'     => round($maximo, 2),
        'porcentaje' => $maximo > 0 ? round(100 * $puntaje / $maximo, 1) : 0,
    ];
}

function examenes_validar_reglas(array $entrada): array
{
    $r = [];
    foreach (['correcta', 'incorrecta', 'blanco'] as $k) {
        $v = str_replace(',', '.', trim((string) ($entrada[$k] ?? '')));
        if ($v === '' || !is_numeric($v) || abs((float) $v) > 1000) {
            throw new RuntimeException('Los puntos deben ser números entre -1000 y 1000.');
        }
        $r[$k] = round((float) $v, 2);
    }
    if ($r['correcta'] <= 0) throw new RuntimeException('Una respuesta correcta debe sumar puntos (mayor que 0).');
    return $r;
}

function examenes_guardar_reglas(array $r): void
{
    Db::actualizar('academias', [
        'puntaje_correcta'   => $r['correcta'],
        'puntaje_incorrecta' => $r['incorrecta'],
        'puntaje_blanco'     => $r['blanco'],
    ], 'id = :id', ['id' => Contexto::exigirAcademia()]);
}

/** Guarda la clave y recalcula las fichas ya escaneadas. Devuelve cuántas recalculó. */
function examenes_guardar_clave(array $examen, string $clave): int
{
    Db::iniciar();
    try {
        Db::actualizar('examenes', ['clave' => $clave], 'id = :id', ['id' => (int) $examen['id']]);
        $filas = Db::todos('SELECT id, respuestas FROM examen_resultados WHERE examen_id = :e', ['e' => (int) $examen['id']]);
        foreach ($filas as $f) {
            $c = examenes_calificar($clave, $f['respuestas']);
            Db::actualizar('examen_resultados', [
                'correctas' => $c['correctas'], 'incorrectas' => $c['incorrectas'], 'blancos' => $c['blancos'],
                'multiples' => $c['multiples'], 'total' => $c['total'],
            ], 'id = :id', ['id' => (int) $f['id']]);
        }
        Db::confirmar();
        return count($filas);
    } catch (Throwable $e) {
        Db::revertir();
        throw $e;
    }
}

/** Revisa que la hoja leída sea la del examen (mismo número de preguntas). */
function examenes_verificar_hoja(array $examen, array $leido): void
{
    if ((int) ($leido['preguntas'] ?? 0) !== (int) $examen['preguntas']) {
        throw new RuntimeException('La hoja leída tiene ' . (int) ($leido['preguntas'] ?? 0) . ' preguntas y el examen tiene '
            . (int) $examen['preguntas'] . '. ¿Es la hoja correcta?');
    }
}

// ================================================================ alumnos

/** Alumno de la academia por su código, con el resultado previo en este examen si lo tiene. */
function examenes_alumno_por_codigo(array $examen, string $codigo): ?array
{
    [$where, $params] = Contexto::filtro('a');
    $params['c'] = strtoupper(trim($codigo));
    $a = Db::uno(
        "SELECT a.id, a.codigo, a.apellido_paterno, a.apellido_materno, a.nombres, a.nivel, a.grado, a.activo,
                c.nombre AS colegio
           FROM alumnos a
      LEFT JOIN colegios c ON c.id = a.colegio_id
          WHERE a.codigo = :c AND $where",
        $params
    );
    if (!$a) return null;
    $a['nombre_completo'] = trim($a['apellido_paterno'] . ' ' . ($a['apellido_materno'] ?? '')) . ', ' . $a['nombres'];
    $a['grado_texto'] = alumnos_grado_texto($a['nivel'], $a['grado']);
    // ¿Le corresponde este examen? (aviso, no impide: puede rendir otro nivel)
    $a['otro_grado'] = $a['nivel'] !== $examen['nivel']
        || ($examen['grado'] !== null && (int) $a['grado'] !== (int) $examen['grado']);
    $a['previo'] = Db::uno(
        'SELECT id, correctas, incorrectas, blancos, total, actualizado_en
           FROM examen_resultados WHERE examen_id = :e AND alumno_id = :a',
        ['e' => (int) $examen['id'], 'a' => (int) $a['id']]
    );
    return $a;
}

function examenes_alumno_exigir(int $id): array
{
    [$where, $params] = Contexto::filtro();
    $params['id'] = $id;
    $a = Db::uno("SELECT id, codigo FROM alumnos WHERE id = :id AND $where", $params);
    if (!$a) throw new RuntimeException('El alumno no pertenece a esta academia.');
    return $a;
}

// ================================================================ escaneo pendiente de guardar
//
// Lo que se guarda es lo que el SERVIDOR leyó, no lo que mande el navegador:
// el escaneo queda en la sesión con un id al azar y «Guardar» solo envía ese id.

const EXAMEN_TMP = RUTA_UPLOADS . '/tmp';

function examenes_tmp_limpiar(): void
{
    foreach (glob(EXAMEN_TMP . '/*.jpg') ?: [] as $f) {
        if (filemtime($f) < time() - 3 * 3600) @unlink($f);       // escaneos abandonados
    }
}

/** Guarda la foto corregida (data URI JPEG de la API) en un archivo temporal. */
function examenes_tmp_imagen(?string $dataUri): ?string
{
    if (!$dataUri || !str_starts_with($dataUri, 'data:image/jpeg;base64,')) return null;
    $bin = base64_decode(substr($dataUri, 23), true);
    if ($bin === false || strlen($bin) > 6 * 1024 * 1024 || !str_starts_with($bin, "\xFF\xD8")) return null;
    if (!is_dir(EXAMEN_TMP) && !mkdir(EXAMEN_TMP, 0775, true)) {
        throw new RuntimeException('No se puede escribir en uploads/. Revisa los permisos de la carpeta.');
    }
    $ruta = EXAMEN_TMP . '/' . bin2hex(random_bytes(12)) . '.jpg';
    file_put_contents($ruta, $bin, LOCK_EX);
    return $ruta;
}

function examenes_escaneo_poner(array $datos): string
{
    $id = bin2hex(random_bytes(12));
    $lista = $_SESSION['_escaneos'] ?? [];
    $lista[$id] = $datos + ['creado' => time()];
    // solo los 8 más recientes: el resto se descarta (y su foto temporal)
    while (count($lista) > 8) {
        $viejo = array_shift($lista);
        if (!empty($viejo['tmp'])) @unlink($viejo['tmp']);
    }
    $_SESSION['_escaneos'] = $lista;
    return $id;
}

function examenes_escaneo_tomar(string $id): array
{
    $e = $_SESSION['_escaneos'][$id] ?? null;
    if (!$e || $e['creado'] < time() - 3 * 3600 || $e['academia_id'] !== Contexto::academiaId()) {
        throw new RuntimeException('Ese escaneo ya no está disponible. Vuelve a escanear la ficha.');
    }
    unset($_SESSION['_escaneos'][$id]);
    return $e;
}

// ================================================================ resultados

/**
 * Fecha y hora en que el alumno entregó el examen (se escribe a mano al guardar).
 * La fecha viene por defecto en hoy; la hora es obligatoria. Desempata el ranking.
 */
function examenes_validar_entrega($fecha, $hora): string
{
    $fecha = trim((string) $fecha) ?: date('Y-m-d');
    $hora  = trim((string) $hora);
    $f = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (!$f || $f->format('Y-m-d') !== $fecha) throw new RuntimeException('La fecha de entrega no es válida.');
    if ($fecha > date('Y-m-d')) throw new RuntimeException('La fecha de entrega no puede ser futura.');
    // if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
    //     throw new RuntimeException('Escribe la hora en que el alumno entregó el examen (hh:mm).');
    // }
    return "$fecha $hora:00";
}

/** Guarda (o reemplaza) la ficha del alumno en el examen. Devuelve el id del resultado. */
function examenes_resultado_guardar(array $esc, string $entregadoEn): int
{
    $examen = examenes_exigir((int) $esc['examen_id']);
    examenes_alumno_exigir((int) $esc['alumno_id']);
    $c = examenes_calificar((string) $examen['clave'], $esc['respuestas']);   // con la clave vigente

    $previo = Db::uno('SELECT id, imagen FROM examen_resultados WHERE examen_id = :e AND alumno_id = :a',
                      ['e' => (int) $examen['id'], 'a' => (int) $esc['alumno_id']]);

    // Foto corregida: de uploads/tmp a uploads/examenes/<academia>/<examen>/
    $imagen = null;
    if (!empty($esc['tmp']) && is_file($esc['tmp'])) {
        $rel = 'examenes/' . (int) $examen['academia_id'] . '/' . (int) $examen['id'];
        $dir = RUTA_UPLOADS . '/' . $rel;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException('No se puede escribir en uploads/. Revisa los permisos de la carpeta.');
        }
        $imagen = $rel . '/' . (int) $esc['alumno_id'] . '-' . bin2hex(random_bytes(6)) . '.jpg';
        if (!rename($esc['tmp'], RUTA_UPLOADS . '/' . $imagen)) $imagen = null;
    }

    $datos = [
        'respuestas'    => $esc['respuestas'],
        'revisar'       => implode(',', $esc['revisar']) ?: null,
        'correctas'     => $c['correctas'],
        'incorrectas'   => $c['incorrectas'],
        'blancos'       => $c['blancos'],
        'multiples'     => $c['multiples'],
        'total'         => $c['total'],
        'imagen'        => $imagen,
        'entregado_en'  => $entregadoEn,
        'escaneado_por' => Auth::id(),
    ];
    if ($previo) {
        Db::actualizar('examen_resultados', $datos, 'id = :id', ['id' => (int) $previo['id']]);
        if ($previo['imagen'] && $previo['imagen'] !== $imagen) examenes_borrar_imagen($previo['imagen']);
        return (int) $previo['id'];
    }
    return Db::insertar('examen_resultados', $datos + [
        'academia_id' => (int) $examen['academia_id'],
        'examen_id'   => (int) $examen['id'],
        'alumno_id'   => (int) $esc['alumno_id'],
    ]);
}

function examenes_borrar_imagen(?string $rel): void
{
    if (!$rel || str_contains($rel, '..')) return;
    $f = RUTA_UPLOADS . '/' . $rel;
    if (is_file($f)) @unlink($f);
}

function examenes_resultados(int $examenId): array
{
    [$where, $params] = Contexto::filtro('r');
    $params['e'] = $examenId;
    $filas = Db::todos(
        "SELECT r.id, r.alumno_id, r.respuestas, r.revisar, r.correctas, r.incorrectas, r.blancos, r.multiples,
                r.total, r.imagen IS NOT NULL AS tiene_imagen, r.entregado_en, r.actualizado_en,
                a.codigo, a.nivel, a.grado,
                CONCAT_WS(', ', TRIM(CONCAT_WS(' ', a.apellido_paterno, a.apellido_materno)), a.nombres) AS alumno
           FROM examen_resultados r
           JOIN alumnos a ON a.id = r.alumno_id
          WHERE r.examen_id = :e AND $where
       ORDER BY r.correctas DESC, a.apellido_paterno, a.apellido_materno, a.nombres",
        $params
    );
    $reglas = examenes_reglas();
    foreach ($filas as &$f) {
        $f += examenes_puntaje($f, $reglas);
        $f['grado_texto'] = alumnos_grado_texto($f['nivel'], $f['grado']);
        $f['revisar'] = $f['revisar'] ? array_map('intval', explode(',', $f['revisar'])) : [];
        $f['tiene_imagen'] = (bool) $f['tiene_imagen'];
    }
    unset($f);
    // Ranking: mayor puntaje primero; a igual puntaje, quien entregó antes.
    // (El puntaje depende de las reglas de la academia, por eso se ordena aquí y no en SQL.)
    // usort($filas, fn($a, $b) => [$b['puntaje'], $a['entregado_en'] ?? '9999'] <=> [$a['puntaje'], $b['entregado_en'] ?? '9999']);
    // foreach ($filas as $i => &$f) $f['puesto'] = $i + 1;
    // return $filas;

    // KOLYN
    // Ranking: mayor puntaje primero; a igual puntaje,
    // quien entregó antes, si ambos tienen hora válida.
    usort($filas, function ($a, $b) {
        // 1. Priorizar siempre el mayor puntaje.
        $comparacion = (float) $b['puntaje'] <=> (float) $a['puntaje'];

        if ($comparacion !== 0) {
            return $comparacion;
        }

        // 2. Obtener las fechas de entrega.
        $fechaA = $a['entregado_en'] ?? null;
        $fechaB = $b['entregado_en'] ?? null;

        // 3. Ignorar las fechas cuya hora sea 00:00:00.
        if ($fechaA && substr($fechaA, 11, 8) === '00:00:00') {
            $fechaA = null;
        }

        if ($fechaB && substr($fechaB, 11, 8) === '00:00:00') {
            $fechaB = null;
        }

        // 4. Comparar las horas solo si ambas son válidas.
        if ($fechaA !== null && $fechaB !== null) {
            return strcmp($fechaA, $fechaB);
        }

        // 5. Si falta una hora válida, conservar el orden relativo.
        return 0;
    });

    foreach ($filas as $i => &$f) {
        $f['puesto'] = $i + 1;
    }
    unset($f);

    return $filas;
}

/** El resultado SOLO si es de la academia actual. */
function examenes_resultado_obtener(int $id): ?array
{
    [$where, $params] = Contexto::filtro();
    $params['id'] = $id;
    return Db::uno("SELECT * FROM examen_resultados WHERE id = :id AND $where", $params);
}

function examenes_resultado_eliminar(int $id): void
{
    $r = examenes_resultado_obtener($id);
    if (!$r) throw new RuntimeException('Ese resultado no existe.');
    Db::eliminar('examen_resultados', 'id = :id', ['id' => $id]);
    examenes_borrar_imagen($r['imagen']);
}
