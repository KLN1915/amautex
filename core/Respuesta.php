<?php
/**
 * Respuestas JSON uniformes para los endpoints AJAX.
 * Todo el front espera siempre { ok, mensaje, datos }.
 */
class Respuesta
{
    public static function json(array $cuerpo, int $codigo = 200): void
    {
        if (!headers_sent()) {
            http_response_code($codigo);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok($datos = null, string $mensaje = 'OK'): void
    {
        self::json(['ok' => true, 'mensaje' => $mensaje, 'datos' => $datos]);
    }

    public static function error(string $mensaje, int $codigo = 400, $datos = null): void
    {
        self::json(['ok' => false, 'mensaje' => $mensaje, 'datos' => $datos], $codigo);
    }
}
