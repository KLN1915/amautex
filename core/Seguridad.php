<?php
/**
 * CSRF, escape de salida y saneo de entrada.
 */
class Seguridad
{
    /** Token CSRF de la sesión (se genera una vez). */
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** <input hidden> listo para pegar en cualquier formulario. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    /** Valida el token: en POST corta la ejecución si no coincide. */
    public static function verificar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $enviado = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!hash_equals($_SESSION['_csrf'] ?? '', (string) $enviado)) {
            Respuesta::error('Token de seguridad inválido. Recarga la página.', 419);
        }
    }

    /** Escape para HTML. */
    public static function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Limpia texto plano de entrada. */
    public static function texto($valor, int $max = 255): string
    {
        return mb_substr(trim(strip_tags((string) $valor)), 0, $max);
    }

    public static function entero($valor): int { return (int) $valor; }

    public static function correo($valor): string
    {
        $v = filter_var(trim((string) $valor), FILTER_VALIDATE_EMAIL);
        return $v === false ? '' : $v;
    }
}
