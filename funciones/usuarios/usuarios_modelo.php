<?php
/**
 * Consultas del módulo Usuarios. Nada de HTML aquí: solo datos.
 *
 * TODA consulta pasa por Contexto::filtro(): un admin de academia jamás
 * alcanza usuarios de otra academia, ni cambiando el id en la petición.
 */
defined('APP_NOMBRE') or exit;

function usuarios_listar(string $busqueda = '', int $pagina = 1, int $porPagina = 20): array
{
    [$filtro, $params] = Contexto::filtro('u');
    $where = $filtro . " AND u.rol <> 'superadmin'";

    if ($busqueda !== '') {
        [$sqlLike, $parLike] = Db::like(['u.nombre', 'u.usuario', 'u.correo'], $busqueda);
        $where .= ' AND ' . $sqlLike;
        $params += $parLike;
    }

    $total  = (int) Db::valor("SELECT COUNT(*) FROM usuarios u WHERE $where", $params);
    $offset = max(0, ($pagina - 1) * $porPagina);

    $filas = Db::todos(
        "SELECT u.id, u.nombre, u.usuario, u.correo, u.rol, u.activo,
                u.ultimo_acceso, u.creado_en, u.academia_id,
                a.nombre AS academia
           FROM usuarios u
      LEFT JOIN academias a ON a.id = u.academia_id
          WHERE $where
       ORDER BY u.nombre
          LIMIT $porPagina OFFSET $offset",
        $params
    );

    return [
        'filas'   => $filas,
        'total'   => $total,
        'paginas' => (int) ceil($total / $porPagina),
        'pagina'  => $pagina,
        'multi'   => academia_id() === null,   // el front muestra la columna Academia
    ];
}

/** Devuelve el usuario SOLO si está dentro del contexto actual. */
function usuarios_obtener(int $id): ?array
{
    [$filtro, $params] = Contexto::filtro('u');
    $params['id'] = $id;
    return Db::uno(
        "SELECT u.id, u.nombre, u.usuario, u.correo, u.rol, u.activo, u.academia_id
           FROM usuarios u
          WHERE u.id = :id AND $filtro AND u.rol <> 'superadmin'",
        $params
    );
}

function usuarios_existe_login(string $usuario, int $excluirId = 0): bool
{
    return (bool) Db::valor(
        'SELECT COUNT(*) FROM usuarios WHERE usuario = :u AND id <> :id',
        ['u' => $usuario, 'id' => $excluirId]
    );
}

/** Cuántos usuarios tiene la academia y cuántos permite su plan. */
function usuarios_cupo(int $academiaId): array
{
    $a = Db::uno('SELECT limite_usuarios FROM academias WHERE id = :id', ['id' => $academiaId]);
    return [
        'usados' => (int) Db::valor('SELECT COUNT(*) FROM usuarios WHERE academia_id = :a', ['a' => $academiaId]),
        'limite' => (int) ($a['limite_usuarios'] ?? 0),
    ];
}

/** Roles que el usuario logueado puede asignar. */
function usuarios_roles_permitidos(): array
{
    $roles = ['operador' => 'Operador', 'consulta' => 'Consulta'];
    if (Auth::esRol('superadmin', 'admin')) {
        $roles = ['admin' => 'Administrador'] + $roles;
    }
    return $roles;
}

/** Valida los datos del formulario. Devuelve [errores, datosLimpios, clave]. */
function usuarios_validar(array $entrada, int $id = 0): array
{
    $errores = [];
    $d = [
        'nombre'  => Seguridad::texto($entrada['nombre']  ?? '', 100),
        'usuario' => Seguridad::texto($entrada['usuario'] ?? '', 50),
        'correo'  => Seguridad::correo($entrada['correo'] ?? ''),
        'rol'     => Seguridad::texto($entrada['rol']     ?? '', 20),
        'activo'  => !empty($entrada['activo']) ? 1 : 0,
    ];

    if ($d['nombre'] === '')  $errores[] = 'El nombre es obligatorio.';
    if ($d['usuario'] === '') $errores[] = 'El usuario es obligatorio.';
    if (!array_key_exists($d['rol'], usuarios_roles_permitidos())) $errores[] = 'Rol inválido o no permitido.';
    if (($entrada['correo'] ?? '') !== '' && $d['correo'] === '') $errores[] = 'El correo no es válido.';
    if ($d['usuario'] !== '' && usuarios_existe_login($d['usuario'], $id)) {
        $errores[] = 'Ese usuario ya está registrado en el sistema.';
    }

    $clave = (string) ($entrada['clave'] ?? '');
    if ($id === 0 && strlen($clave) < 6)                 $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($id > 0 && $clave !== '' && strlen($clave) < 6)  $errores[] = 'La contraseña debe tener al menos 6 caracteres.';

    return [$errores, $d, $clave];
}

/** Crea el usuario dentro de la academia actual, respetando el límite del plan. */
function usuarios_crear(array $datos, string $clave): int
{
    $academiaId = Contexto::exigirAcademia();

    $cupo = usuarios_cupo($academiaId);
    if ($cupo['limite'] > 0 && $cupo['usados'] >= $cupo['limite']) {
        throw new RuntimeException(
            'La academia llegó al límite de ' . $cupo['limite'] . ' usuarios de su plan.'
        );
    }

    $datos['academia_id'] = $academiaId;
    $datos['clave']       = password_hash($clave, PASSWORD_DEFAULT);
    $datos['creado_en']   = date('Y-m-d H:i:s');
    return Db::insertar('usuarios', $datos);
}

function usuarios_actualizar(int $id, array $datos, string $clave = ''): int
{
    if (!usuarios_obtener($id)) {
        throw new RuntimeException('El usuario no pertenece a esta academia.');
    }
    if ($clave !== '') {
        $datos['clave'] = password_hash($clave, PASSWORD_DEFAULT);
        $datos['debe_cambiar_clave'] = 0;
    }
    return Db::actualizar('usuarios', $datos, 'id = :id', ['id' => $id]);
}

function usuarios_cambiar_estado(int $id, int $activo): int
{
    if (!usuarios_obtener($id)) {
        throw new RuntimeException('El usuario no pertenece a esta academia.');
    }
    return Db::actualizar('usuarios', ['activo' => $activo], 'id = :id', ['id' => $id]);
}

function usuarios_eliminar(int $id): int
{
    if (!usuarios_obtener($id)) {
        throw new RuntimeException('El usuario no pertenece a esta academia.');
    }
    return Db::eliminar('usuarios', 'id = :id', ['id' => $id]);
}
