<?php
/**
 * Render de vistas dentro del layout (header + sidebar + footer).
 */
class Vista
{
    /** Renderiza vistas/modulos/{$vista}.php con $datos disponibles como variables. */
    public static function render(string $vista, array $datos = [], ?string $titulo = null): void
    {
        $archivo = RUTA_VISTA . '/modulos/' . $vista . '.php';
        if (!is_file($archivo)) {
            self::error(404, 'Vista no encontrada: ' . $vista);
        }
        $titulo = $titulo ?? ucfirst(str_replace('_', ' ', $vista));
        extract($datos, EXTR_SKIP);

        require RUTA_VISTA . '/layout/header.php';
        require RUTA_VISTA . '/layout/sidebar.php';
        require $archivo;
        require RUTA_VISTA . '/layout/footer.php';
        exit;   // la vista cierra la peticion: nada se ejecuta despues
    }

    /** Vista suelta, sin layout (login, PDF, impresiones). */
    public static function limpia(string $vista, array $datos = []): void
    {
        $archivo = RUTA_VISTA . '/modulos/' . $vista . '.php';
        if (!is_file($archivo)) self::error(404, 'Vista no encontrada: ' . $vista);
        extract($datos, EXTR_SKIP);
        require $archivo;
        exit;
    }

    /** Fragmento reutilizable (tabla, modal, fila) devuelto como string. */
    public static function parcial(string $vista, array $datos = []): string
    {
        $archivo = RUTA_VISTA . '/modulos/' . $vista . '.php';
        if (!is_file($archivo)) return '';
        extract($datos, EXTR_SKIP);
        ob_start();
        require $archivo;
        return (string) ob_get_clean();
    }

    public static function error(int $codigo, string $mensaje = ''): void
    {
        http_response_code($codigo);
        $archivo = RUTA_VISTA . '/errores/' . $codigo . '.php';
        if (is_file($archivo)) { require $archivo; } else { echo "<h1>Error $codigo</h1><p>" . Seguridad::e($mensaje) . '</p>'; }
        exit;
    }

    public static function esAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
