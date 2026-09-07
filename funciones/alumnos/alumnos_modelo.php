<?php
/**
 * Consultas del módulo Alumnos.
 * Cada alumno pertenece a UNA academia: todas las consultas pasan
 * por Contexto::filtro().
 */
defined('APP_NOMBRE') or exit;

/** Niveles educativos y los grados válidos de cada uno. */
function alumnos_niveles(): array
{
    return [
        'inicial'    => ['titulo' => 'Inicial',     'grados' => [3 => '3 años', 4 => '4 años', 5 => '5 años']],
        'primaria'   => ['titulo' => 'Primaria',    'grados' => [1 => '1.º', 2 => '2.º', 3 => '3.º', 4 => '4.º', 5 => '5.º', 6 => '6.º']],
        'secundaria' => ['titulo' => 'Secundaria',  'grados' => [1 => '1.º', 2 => '2.º', 3 => '3.º', 4 => '4.º', 5 => '5.º']],
        'otro'       => ['titulo' => 'Otro / egresado', 'grados' => []],
    ];
}

/** Tipos de alumno y de qué tarifa de la academia cobra cada uno. */
function alumnos_tipos(): array
{
    return [
        'estatal'    => ['titulo' => 'Estatal',    'tarifa' => 'tarifa_estatal',    'colegio' => true],
        'particular' => ['titulo' => 'Particular', 'tarifa' => 'tarifa_particular', 'colegio' => true],
        'libre'      => ['titulo' => 'Alumno libre', 'tarifa' => 'tarifa_libre',    'colegio' => false],
    ];
}

/** Cuota que le corresponde al tipo, según lo configurado en la academia. */
function alumnos_tarifa(string $tipo): float
{
    $tipos = alumnos_tipos();
    if (!isset($tipos[$tipo])) return 0.0;
    $academia = Contexto::academia();
    return (float) ($academia[$tipos[$tipo]['tarifa']] ?? 0);
}

/** Las tres tarifas de la academia actual, para mostrarlas en pantalla. */
function alumnos_tarifas(): array
{
    $r = [];
    foreach (alumnos_tipos() as $clave => $t) {
        $r[$clave] = alumnos_tarifa($clave);
    }
    return $r;
}

/** Texto legible del grado según el nivel: "3.º de secundaria". */
function alumnos_grado_texto(string $nivel, $grado): string
{
    $niveles = alumnos_niveles();
    if (!isset($niveles[$nivel])) return '';
    if ($grado === null || $grado === '') return $niveles[$nivel]['titulo'];
    $etiqueta = $niveles[$nivel]['grados'][(int) $grado] ?? $grado;
    return $etiqueta . ' de ' . mb_strtolower($niveles[$nivel]['titulo']);
}

function alumnos_listar(array $f = [], int $pagina = 1, int $porPagina = 20): array
{
    [$where, $params] = Contexto::filtro('a');

    if (!empty($f['q'])) {
        [$sqlLike, $parLike] = Db::like(
            ['a.apellido_paterno', 'a.apellido_materno', 'a.nombres', 'a.codigo', 'c.nombre'],
            $f['q']
        );
        $where .= ' AND ' . $sqlLike;
        $params += $parLike;
    }
    if (!empty($f['nivel'])) {
        $where .= ' AND a.nivel = :nivel';
        $params['nivel'] = $f['nivel'];
    }
    if (!empty($f['grado'])) {
        $where .= ' AND a.grado = :grado';
        $params['grado'] = (int) $f['grado'];
    }
    if (!empty($f['tipo'])) {
        $where .= ' AND a.tipo_alumno = :tipo';
        $params['tipo'] = $f['tipo'];
    }
    if (!empty($f['colegio'])) {
        $where .= ' AND a.colegio_id = :col';
        $params['col'] = (int) $f['colegio'];
    }
    if (isset($f['activo']) && $f['activo'] !== '') {
        $where .= ' AND a.activo = :activo';
        $params['activo'] = (int) $f['activo'];
    }

    $total = (int) Db::valor(
        "SELECT COUNT(*) FROM alumnos a LEFT JOIN colegios c ON c.id = a.colegio_id WHERE $where",
        $params
    );
    $offset = max(0, ($pagina - 1) * $porPagina);

    /* Orden por defecto: del más reciente al más antiguo, para que el alumno
       recién registrado quede arriba. El `id DESC` no sobra: varios alumnos
       cargados en el mismo segundo comparten `creado_en`, y sin él MySQL los
       devolvería en cualquier orden. */
    $filas = Db::todos(
        "SELECT a.id, a.codigo, a.apellido_paterno, a.apellido_materno, a.nombres,
                CONCAT_WS(', ', TRIM(CONCAT_WS(' ', a.apellido_paterno, a.apellido_materno)), a.nombres)
                    AS nombre_completo,
                a.tipo_alumno, a.colegio_id, c.nombre AS colegio,
                a.nivel, a.grado, a.activo
           FROM alumnos a
      LEFT JOIN colegios c ON c.id = a.colegio_id
          WHERE $where
       ORDER BY a.creado_en DESC, a.id DESC
          LIMIT $porPagina OFFSET $offset",
        $params
    );

    // Textos y cuota, calculados en un solo lugar
    $tipos = alumnos_tipos();
    foreach ($filas as &$fila) {
        $fila['grado_texto'] = alumnos_grado_texto($fila['nivel'], $fila['grado']);
        $fila['tipo_texto']  = $tipos[$fila['tipo_alumno']]['titulo'] ?? $fila['tipo_alumno'];
        $fila['cuota']       = alumnos_tarifa($fila['tipo_alumno']);
    }
    unset($fila);

    return [
        'filas'   => $filas,
        'total'   => $total,
        'paginas' => (int) ceil($total / $porPagina),
        'pagina'  => $pagina,
    ];
}

/** Devuelve el alumno SOLO si pertenece a la academia actual. */
function alumnos_obtener(int $id): ?array
{
    [$where, $params] = Contexto::filtro();
    $params['id'] = $id;
    return Db::uno("SELECT * FROM alumnos WHERE id = :id AND $where", $params);
}

/** Colegios del catálogo de la academia (opcionalmente de un solo tipo). */
function colegios_listar(string $tipo = ''): array
{
    [$where, $params] = Contexto::filtro();
    if ($tipo === 'estatal' || $tipo === 'particular') {
        $where .= ' AND tipo = :t';
        $params['t'] = $tipo;
    }
    return Db::todos(
        "SELECT id, nombre, tipo FROM colegios WHERE $where AND activo = 1 ORDER BY nombre",
        $params
    );
}

/** El colegio SOLO si pertenece a la academia actual. */
function colegios_obtener(int $id): ?array
{
    [$where, $params] = Contexto::filtro();
    $params['id'] = $id;
    return Db::uno("SELECT * FROM colegios WHERE id = :id AND $where", $params);
}

/**
 * Busca un colegio de la academia por nombre exacto, activo o no.
 * El nombre es único por academia (uq_colegio_nombre), así que como mucho hay uno.
 */
function colegios_por_nombre(string $nombre): ?array
{
    [$where, $params] = Contexto::filtro();
    $params['n'] = Seguridad::texto($nombre, 150);
    return Db::uno("SELECT * FROM colegios WHERE nombre = :n AND $where", $params);
}

/**
 * Devuelve el id del colegio, creándolo si el nombre es nuevo.
 * Así el combobox permite agregar un colegio sin salir del formulario.
 *
 * Si el nombre ya existe pero estaba desactivado, se reactiva: si no, el alumno
 * quedaría amarrado a un colegio que el combobox no lista (solo trae activos) y
 * al editarlo el campo aparecería vacío.
 */
function colegios_asegurar(string $nombre, string $tipo): int
{
    $academiaId = Contexto::exigirAcademia();
    $nombre = Seguridad::texto($nombre, 150);

    $existente = colegios_por_nombre($nombre);
    if ($existente) {
        if ((int) $existente['activo'] !== 1) {
            Db::actualizar('colegios', ['activo' => 1], 'id = :id', ['id' => (int) $existente['id']]);
        }
        return (int) $existente['id'];
    }

    return Db::insertar('colegios', [
        'academia_id' => $academiaId,
        'nombre'      => $nombre,
        'tipo'        => $tipo === 'particular' ? 'particular' : 'estatal',
        'activo'      => 1,
        'creado_en'   => date('Y-m-d H:i:s'),
    ]);
}

/** Resumen por nivel y grado, para el panel. */
function alumnos_resumen(): array
{
    [$where, $params] = Contexto::filtro();
    return Db::todos(
        "SELECT nivel, grado, COUNT(*) AS total
           FROM alumnos
          WHERE $where AND activo = 1
       GROUP BY nivel, grado
       ORDER BY FIELD(nivel, 'inicial', 'primaria', 'secundaria', 'otro'), grado",
        $params
    );
}

/** Cuántos alumnos activos hay de cada tipo (para el pie de la pantalla). */
function alumnos_resumen_tipo(): array
{
    [$where, $params] = Contexto::filtro();
    return Db::todos(
        "SELECT tipo_alumno, COUNT(*) AS total
           FROM alumnos
          WHERE $where AND activo = 1
       GROUP BY tipo_alumno",
        $params
    );
}

/**
 * Todo lo que vive fuera de la tabla y cambia al crear, editar o borrar un
 * alumno: los dos paneles de abajo, el contador del plan y el catálogo de
 * colegios del filtro. El front lo repinta sin recargar la página.
 *
 * Los textos de grado y las tarifas se calculan aquí y no en JavaScript,
 * para no tener la misma regla escrita en dos idiomas.
 */
function alumnos_panel(): array
{
    $academiaId = Contexto::exigirAcademia();

    $resumen = [];
    foreach (alumnos_resumen() as $r) {
        $resumen[] = [
            'texto' => alumnos_grado_texto($r['nivel'], $r['grado']),
            'total' => (int) $r['total'],
        ];
    }

    $porTipo = array_column(alumnos_resumen_tipo(), 'total', 'tipo_alumno');
    $cuotas  = [];
    foreach (alumnos_tipos() as $clave => $t) {
        $cuotas[] = [
            'titulo'  => $t['titulo'],
            'alumnos' => (int) ($porTipo[$clave] ?? 0),
            'tarifa'  => alumnos_tarifa($clave),
        ];
    }

    return [
        'resumen'  => $resumen,
        'cuotas'   => $cuotas,
        'cupo'     => alumnos_cupo($academiaId),
        'colegios' => colegios_listar(),
    ];
}

function alumnos_existe_codigo(string $codigo, int $excluirId = 0): bool
{
    [$where, $params] = Contexto::filtro();
    $params['c']  = $codigo;
    $params['id'] = $excluirId;
    return (bool) Db::valor("SELECT COUNT(*) FROM alumnos WHERE codigo = :c AND id <> :id AND $where", $params);
}

/** Siguiente código correlativo de la academia: A0001, A0002… */
function alumnos_siguiente_codigo(): string
{
    $academiaId = Contexto::exigirAcademia();
    $ultimo = (int) Db::valor(
        "SELECT MAX(CAST(SUBSTRING(codigo, 2) AS UNSIGNED))
           FROM alumnos
          WHERE academia_id = :a AND codigo REGEXP '^A[0-9]+$'",
        ['a' => $academiaId]
    );
    return 'A' . str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
}

/** Cuántos alumnos tiene la academia y cuántos permite su plan. */
function alumnos_cupo(int $academiaId): array
{
    $a = Db::uno('SELECT limite_alumnos FROM academias WHERE id = :id', ['id' => $academiaId]);
    return [
        'usados' => (int) Db::valor('SELECT COUNT(*) FROM alumnos WHERE academia_id = :a', ['a' => $academiaId]),
        'limite' => (int) ($a['limite_alumnos'] ?? 0),
    ];
}

/** Valida el formulario. Devuelve [errores, datosLimpios]. */
function alumnos_validar(array $entrada, int $id = 0): array
{
    $errores = [];
    $niveles = alumnos_niveles();

    $nivel = (string) ($entrada['nivel'] ?? '');
    if (!isset($niveles[$nivel])) $nivel = 'secundaria';

    $grado = trim((string) ($entrada['grado'] ?? ''));
    $grado = $grado === '' ? null : (int) $grado;

    // Estatal / Particular / Libre: define el colegio y la cuota
    $tipos = alumnos_tipos();
    $tipo  = (string) ($entrada['tipo_alumno'] ?? '');
    if (!isset($tipos[$tipo])) $tipo = 'estatal';

    $d = [
        'codigo'      => strtoupper(Seguridad::texto($entrada['codigo'] ?? '', 20)),
        'apellido_paterno' => mb_strtoupper(Seguridad::texto($entrada['apellido_paterno'] ?? '', 60)),
        'apellido_materno' => mb_strtoupper(Seguridad::texto($entrada['apellido_materno'] ?? '', 60)),
        'nombres'     => mb_strtoupper(Seguridad::texto($entrada['nombres'] ?? '', 100)),
        'tipo_alumno' => $tipo,
        'colegio_id'  => null,
        'nivel'       => $nivel,
        'grado'       => $grado,
        'activo'      => !empty($entrada['activo']) ? 1 : 0,
    ];

    if ($d['apellido_paterno'] === '') $errores[] = 'El apellido paterno es obligatorio.';
    if ($d['nombres'] === '')          $errores[] = 'Los nombres son obligatorios.';

    // El grado tiene que existir dentro del nivel elegido
    $gradosValidos = $niveles[$nivel]['grados'];
    if ($gradosValidos && ($d['grado'] === null || !isset($gradosValidos[$d['grado']]))) {
        $errores[] = 'Elige un grado válido para ' . mb_strtolower($niveles[$nivel]['titulo']) . '.';
    }
    if (!$gradosValidos) $d['grado'] = null;

    if ($d['codigo'] !== '' && alumnos_existe_codigo($d['codigo'], $id)) {
        $errores[] = 'Ya existe un alumno con el código ' . $d['codigo'] . ' en esta academia.';
    }

    // Colegio: obligatorio salvo que el alumno sea libre.
    // Puede venir un id del combobox o un nombre nuevo escrito por el usuario.
    if ($tipos[$tipo]['colegio']) {
        $colegioId     = (int) ($entrada['colegio_id'] ?? 0);
        $colegioNuevo  = Seguridad::texto($entrada['colegio_nuevo'] ?? '', 150);

        if ($colegioId > 0) {
            $colegio = colegios_obtener($colegioId);
            if (!$colegio) {
                $errores[] = 'El colegio elegido no pertenece a esta academia.';
            } elseif ($colegio['tipo'] !== $tipo) {
                $errores[] = 'El colegio "' . $colegio['nombre'] . '" es ' . $colegio['tipo']
                           . ', no coincide con el tipo elegido.';
            } else {
                $d['colegio_id'] = $colegioId;
            }
        } elseif ($colegioNuevo !== '') {
            // El nombre es único por academia, así que "agregar uno nuevo" puede
            // toparse con uno ya registrado con OTRO tipo. Sin este control el
            // alumno quedaba apuntando a un colegio del tipo equivocado, y al
            // editarlo el combobox (filtrado por tipo) no lo mostraba.
            $existente = colegios_por_nombre($colegioNuevo);
            if ($existente && $existente['tipo'] !== $tipo) {
                $errores[] = 'Ya existe un colegio llamado "' . $existente['nombre'] . '" registrado como '
                           . $existente['tipo'] . '. Cambia el tipo de alumno para elegirlo de la lista, '
                           . 'o regístralo con otro nombre.';
            } else {
                $d['colegio_id'] = colegios_asegurar($colegioNuevo, $tipo);
            }
        } else {
            $errores[] = 'Elige el colegio (o marca el alumno como libre).';
        }
    }

    // El apellido materno vacío se guarda como NULL, no como cadena vacía
    if ($d['apellido_materno'] === '') $d['apellido_materno'] = null;

    return [$errores, $d];
}

function alumnos_crear(array $datos): int
{
    $academiaId = Contexto::exigirAcademia();

    $cupo = alumnos_cupo($academiaId);
    if ($cupo['limite'] > 0 && $cupo['usados'] >= $cupo['limite']) {
        throw new RuntimeException('La academia llegó al límite de ' . $cupo['limite'] . ' alumnos de su plan.');
    }
    if (($datos['codigo'] ?? '') === '') {
        $datos['codigo'] = alumnos_siguiente_codigo();
    }

    $datos['academia_id'] = $academiaId;
    $datos['creado_en']   = date('Y-m-d H:i:s');
    return Db::insertar('alumnos', $datos);
}

function alumnos_actualizar(int $id, array $datos): int
{
    $actual = alumnos_obtener($id);
    if (!$actual) throw new RuntimeException('El alumno no pertenece a esta academia.');

    if (($datos['codigo'] ?? '') === '') $datos['codigo'] = $actual['codigo'];
    return Db::actualizar('alumnos', $datos, 'id = :id', ['id' => $id]);
}

function alumnos_cambiar_estado(int $id, int $activo): int
{
    if (!alumnos_obtener($id)) throw new RuntimeException('El alumno no pertenece a esta academia.');
    return Db::actualizar('alumnos', ['activo' => $activo], 'id = :id', ['id' => $id]);
}

function alumnos_eliminar(int $id): int
{
    if (!alumnos_obtener($id)) throw new RuntimeException('El alumno no pertenece a esta academia.');
    return Db::eliminar('alumnos', 'id = :id', ['id' => $id]);
}
