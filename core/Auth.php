<?php
/**
 * Sesión, login y permisos por rol.
 *
 * Roles:
 *   superadmin -> dueño del sistema. Sin academia. Gestiona academias y credenciales.
 *   admin      -> administrador DE SU academia.
 *   operador   -> carga y edita datos de su academia.
 *   consulta   -> solo lectura de su academia.
 */
class Auth
{
    /** Motivo del último login fallido (para mostrarlo en la pantalla de ingreso). */
    public static string $error = '';

    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name(SESION_NOMBRE);
        session_set_cookie_params([
            'lifetime' => SESION_VIDA,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();

        // Expiración por inactividad
        if (isset($_SESSION['_ultimo']) && time() - $_SESSION['_ultimo'] > SESION_VIDA) {
            self::salir();
        }
        $_SESSION['_ultimo'] = time();
    }

    /**
     * Valida credenciales y abre la sesión.
     * Además del usuario, verifica que su academia siga activa y con plan vigente.
     */
    public static function login(string $usuario, string $clave): bool
    {
        self::$error = 'Usuario o contraseña incorrectos.';

        $u = Db::uno(
            'SELECT u.id, u.usuario, u.nombre, u.clave, u.rol, u.activo, u.academia_id, u.debe_cambiar_clave,
                    a.nombre AS academia_nombre, a.codigo AS academia_codigo,
                    a.color  AS academia_color,  a.estado AS academia_estado, a.fin_plan
               FROM usuarios u
          LEFT JOIN academias a ON a.id = u.academia_id
              WHERE u.usuario = :u
              LIMIT 1',
            ['u' => $usuario]
        );

        if (!$u || !password_verify($clave, $u['clave'])) {
            return false;
        }
        if (!$u['activo']) {
            self::$error = 'Tu usuario está desactivado. Comunícate con el administrador.';
            return false;
        }

        // Validaciones de alquiler: no aplican al superadmin (academia_id NULL)
        if ($u['academia_id'] !== null) {
            if ($u['academia_estado'] !== 'activa') {
                self::$error = 'El acceso de tu academia está suspendido. Comunícate con el proveedor del sistema.';
                return false;
            }
            if (!empty($u['fin_plan']) && $u['fin_plan'] < date('Y-m-d')) {
                self::$error = 'El plan de tu academia venció el ' . date('d/m/Y', strtotime($u['fin_plan'])) . '.';
                return false;
            }
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'               => (int) $u['id'],
            'usuario'          => $u['usuario'],
            'nombre'           => $u['nombre'],
            'rol'              => $u['rol'],
            'academia_id'      => $u['academia_id'] === null ? null : (int) $u['academia_id'],
            'academia_nombre'  => $u['academia_nombre'],
            'academia_codigo'  => $u['academia_codigo'],
            'academia_color'   => $u['academia_color'] ?: '#2563eb',
            'debe_cambiar_clave' => (int) $u['debe_cambiar_clave'],
        ];
        // El superadmin arranca sin academia seleccionada (vista global).
        $_SESSION['academia_ctx'] = $_SESSION['usuario']['academia_id'];

        Db::run('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id', ['id' => $u['id']]);
        self::$error = '';
        return true;
    }

    public static function salir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }

    public static function logueado(): bool { return isset($_SESSION['usuario']); }

    /** Datos del usuario actual (o una clave suelta). */
    public static function usuario(?string $clave = null)
    {
        $u = $_SESSION['usuario'] ?? null;
        if ($clave === null) return $u;
        return $u[$clave] ?? null;
    }

    public static function id(): int { return (int) self::usuario('id'); }

    public static function rol(): string { return (string) self::usuario('rol'); }

    public static function esSuper(): bool { return self::rol() === 'superadmin'; }

    /** Academia a la que pertenece el usuario (NULL en el superadmin). */
    public static function academiaPropia(): ?int
    {
        $a = self::usuario('academia_id');
        return $a === null ? null : (int) $a;
    }

    /** ¿Tiene una clave asignada por el administrador que debe reemplazar? */
    public static function debeCambiarClave(): bool
    {
        return (bool) self::usuario('debe_cambiar_clave');
    }

    public static function esRol(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    /** Corta la ejecución si no hay sesión. */
    public static function exigir(): void
    {
        if (!self::logueado()) {
            if (Vista::esAjax()) Respuesta::error('Sesión expirada.', 401);
            header('Location: ' . BASE_URL . 'index.php?p=login');
            exit;
        }
    }

    /** Corta la ejecución si el rol no está permitido. */
    public static function exigirRol(string ...$roles): void
    {
        self::exigir();
        if (!self::esRol(...$roles)) {
            if (Vista::esAjax()) Respuesta::error('No tienes permiso para esta acción.', 403);
            Vista::error(403, 'No tienes permiso para ver esta sección.');
        }
    }
}
