<?php
/**
 * Consultas del módulo Academias. Uso exclusivo del superadmin:
 * aquí NO se filtra por contexto porque este módulo administra todas.
 */
defined('APP_NOMBRE') or exit;

function academias_listar(string $busqueda = '', string $estado = '', int $pagina = 1, int $porPagina = 15): array
{
    $where  = '1=1';
    $params = [];

    if ($busqueda !== '') {
        [$sqlLike, $parLike] = Db::like(['a.nombre', 'a.codigo', 'a.ruc'], $busqueda);
        $where .= ' AND ' . $sqlLike;
        $params += $parLike;
    }
    if ($estado === 'activa' || $estado === 'suspendida') {
        $where .= ' AND a.estado = :e';
        $params['e'] = $estado;
    } elseif ($estado === 'vencida') {
        $where .= ' AND a.fin_plan IS NOT NULL AND a.fin_plan < CURDATE()';
    }

    $total  = (int) Db::valor("SELECT COUNT(*) FROM academias a WHERE $where", $params);
    $offset = max(0, ($pagina - 1) * $porPagina);

    $filas = Db::todos(
        "SELECT a.*,
                (SELECT COUNT(*) FROM usuarios u WHERE u.academia_id = a.id)              AS usuarios,
                (SELECT COUNT(*) FROM alumnos  al WHERE al.academia_id = a.id)            AS alumnos,
                CASE WHEN a.fin_plan IS NULL THEN NULL
                     ELSE DATEDIFF(a.fin_plan, CURDATE()) END                             AS dias_restantes
           FROM academias a
          WHERE $where
       ORDER BY a.nombre
          LIMIT $porPagina OFFSET $offset",
        $params
    );

    return [
        'filas'   => $filas,
        'total'   => $total,
        'paginas' => (int) ceil($total / $porPagina),
        'pagina'  => $pagina,
    ];
}

function academias_obtener(int $id): ?array
{
    return Db::uno('SELECT * FROM academias WHERE id = :id', ['id' => $id]);
}

function academias_existe_codigo(string $codigo, int $excluirId = 0): bool
{
    return (bool) Db::valor(
        'SELECT COUNT(*) FROM academias WHERE codigo = :c AND id <> :id',
        ['c' => $codigo, 'id' => $excluirId]
    );
}

/** Valida el formulario de academia. Devuelve [errores, datos]. */
function academias_validar(array $entrada, int $id = 0): array
{
    $errores = [];
    $d = [
        'nombre'          => Seguridad::texto($entrada['nombre'] ?? '', 120),
        'codigo'          => strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string) ($entrada['codigo'] ?? ''))),
        'ruc'             => Seguridad::texto($entrada['ruc'] ?? '', 20),
        'correo'          => Seguridad::correo($entrada['correo'] ?? ''),
        'telefono'        => Seguridad::texto($entrada['telefono'] ?? '', 30),
        'direccion'       => Seguridad::texto($entrada['direccion'] ?? '', 180),
        'color'           => preg_match('/^#[0-9a-f]{6}$/i', (string) ($entrada['color'] ?? '')) ? $entrada['color'] : '#2563eb',
        'plan'            => in_array($entrada['plan'] ?? '', ['prueba', 'basico', 'pro'], true) ? $entrada['plan'] : 'prueba',
        'estado'          => ($entrada['estado'] ?? 'activa') === 'suspendida' ? 'suspendida' : 'activa',
        'limite_usuarios' => max(1, (int) ($entrada['limite_usuarios'] ?? 10)),
        'limite_alumnos'  => max(1, (int) ($entrada['limite_alumnos'] ?? 200)),
        'inicio_plan'     => fecha_sql($entrada['inicio_plan'] ?? ''),
        'fin_plan'        => fecha_sql($entrada['fin_plan'] ?? ''),
    ];

    if ($d['nombre'] === '')  $errores[] = 'El nombre de la academia es obligatorio.';
    if ($d['codigo'] === '')  $errores[] = 'El código es obligatorio (solo letras, números y guiones).';
    if ($d['codigo'] !== '' && academias_existe_codigo($d['codigo'], $id)) $errores[] = 'Ese código ya está usado por otra academia.';
    if (($entrada['correo'] ?? '') !== '' && $d['correo'] === '') $errores[] = 'El correo no es válido.';
    if ($d['inicio_plan'] && $d['fin_plan'] && $d['fin_plan'] < $d['inicio_plan']) {
        $errores[] = 'La fecha de vencimiento no puede ser anterior al inicio del plan.';
    }

    return [$errores, $d];
}

/**
 * Crea la academia junto con su usuario administrador, en una transacción:
 * o se crean los dos, o no se crea ninguno.
 */
function academias_crear(array $datos, array $admin): int
{
    Db::iniciar();
    try {
        $id = Db::insertar('academias', $datos);
        Db::insertar('usuarios', [
            'academia_id'        => $id,
            'nombre'             => $admin['nombre'],
            'usuario'            => $admin['usuario'],
            'correo'             => $admin['correo'] ?: null,
            'clave'              => password_hash($admin['clave'], PASSWORD_DEFAULT),
            'rol'                => 'admin',
            'activo'             => 1,
            'debe_cambiar_clave' => 1,
            'creado_en'          => date('Y-m-d H:i:s'),
        ]);
        Db::confirmar();
        return $id;
    } catch (Throwable $e) {
        Db::revertir();
        throw $e;
    }
}

function academias_actualizar(int $id, array $datos): int
{
    return Db::actualizar('academias', $datos, 'id = :id', ['id' => $id]);
}

function academias_cambiar_estado(int $id, string $estado): int
{
    return Db::actualizar('academias', ['estado' => $estado], 'id = :id', ['id' => $id]);
}

/** Extiende el alquiler N meses desde el vencimiento actual (o desde hoy si ya venció). */
function academias_renovar(int $id, int $meses): ?string
{
    $a = academias_obtener($id);
    if (!$a) return null;

    $base  = (!empty($a['fin_plan']) && $a['fin_plan'] >= date('Y-m-d')) ? $a['fin_plan'] : date('Y-m-d');
    $nuevo = date('Y-m-d', strtotime($base . ' +' . $meses . ' month'));

    Db::actualizar('academias', ['fin_plan' => $nuevo, 'estado' => 'activa'], 'id = :id', ['id' => $id]);
    return $nuevo;
}

function academias_eliminar(int $id): int
{
    // La FK ON DELETE CASCADE arrastra usuarios y alumnos de esa academia.
    return Db::eliminar('academias', 'id = :id', ['id' => $id]);
}

// ---------------- Credenciales ----------------

/** Usuarios con acceso de una academia (para la pantalla de credenciales). */
function academias_credenciales(int $academiaId): array
{
    return Db::todos(
        "SELECT id, nombre, usuario, correo, rol, activo, debe_cambiar_clave, ultimo_acceso
           FROM usuarios
          WHERE academia_id = :a
       ORDER BY FIELD(rol, 'admin', 'operador', 'consulta'), nombre",
        ['a' => $academiaId]
    );
}

/** Genera una contraseña legible para entregar al cliente. */
function academias_clave_aleatoria(int $largo = 10): string
{
    $letras = 'abcdefghjkmnpqrstuvwxyz';
    $numeros = '23456789';
    $clave = '';
    for ($i = 0; $i < $largo - 3; $i++) $clave .= $letras[random_int(0, strlen($letras) - 1)];
    for ($i = 0; $i < 3; $i++)          $clave .= $numeros[random_int(0, strlen($numeros) - 1)];
    return $clave;
}

/** Restablece la clave de un usuario de esa academia y devuelve la nueva. */
function academias_resetear_clave(int $academiaId, int $usuarioId): ?string
{
    $u = Db::uno(
        'SELECT id FROM usuarios WHERE id = :id AND academia_id = :a',
        ['id' => $usuarioId, 'a' => $academiaId]
    );
    if (!$u) return null;

    $clave = academias_clave_aleatoria();
    Db::actualizar('usuarios', [
        'clave'              => password_hash($clave, PASSWORD_DEFAULT),
        'debe_cambiar_clave' => 1,
    ], 'id = :id', ['id' => $usuarioId]);

    return $clave;
}
