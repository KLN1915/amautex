<?php
/**
 * Contexto de academia (multi-tenant).
 *
 * REGLA DE ORO del sistema: ninguna consulta de un módulo operativo se
 * escribe sin filtrar por Contexto::academiaId(). Ese filtro es lo que
 * impide que una academia vea los datos de otra.
 *
 *   Usuario de academia -> el contexto es SIEMPRE su propia academia
 *                          (no puede cambiarlo aunque manipule la URL).
 *   Superadmin          -> puede pararse en una academia (Contexto::cambiar)
 *                          o quedarse en la vista global (NULL).
 */
class Contexto
{
    private static ?array $cache = null;

    /** Academia sobre la que se está trabajando. NULL = superadmin en vista global. */
    public static function academiaId(): ?int
    {
        if (!Auth::logueado()) return null;

        if (!Auth::esSuper()) {
            return Auth::academiaPropia();          // blindado: ignora la sesión manipulada
        }
        $ctx = $_SESSION['academia_ctx'] ?? null;
        return $ctx === null ? null : (int) $ctx;
    }

    /** Para módulos operativos: exige estar parado en una academia concreta. */
    public static function exigirAcademia(): int
    {
        $id = self::academiaId();
        if ($id === null) {
            if (Vista::esAjax()) Respuesta::error('Selecciona una academia para continuar.', 409);
            Vista::error(403, 'Selecciona una academia en el menú superior para trabajar en este módulo.');
        }
        return $id;
    }

    /** Fila completa de la academia actual (o null en vista global). */
    public static function academia(): ?array
    {
        $id = self::academiaId();
        if ($id === null) return null;
        if (self::$cache === null || (int) self::$cache['id'] !== $id) {
            self::$cache = Db::uno('SELECT * FROM academias WHERE id = :id', ['id' => $id]);
        }
        return self::$cache;
    }

    public static function nombre(): string
    {
        return self::academia()['nombre'] ?? 'Todas las academias';
    }

    public static function color(): string
    {
        return self::academia()['color'] ?? '#2563eb';
    }

    /** Solo superadmin: cambia la academia en la que está parado. */
    public static function cambiar(?int $academiaId): void
    {
        if (!Auth::esSuper()) {
            Respuesta::error('No puedes cambiar de academia.', 403);
        }
        if ($academiaId !== null && !Db::valor('SELECT id FROM academias WHERE id = :id', ['id' => $academiaId])) {
            Respuesta::error('La academia no existe.', 404);
        }
        self::$cache = null;
        $_SESSION['academia_ctx'] = $academiaId;
    }

    /** Lista corta para el selector del superadmin. */
    public static function listaAcademias(): array
    {
        if (!Auth::esSuper()) return [];
        return Db::todos('SELECT id, nombre, codigo, estado FROM academias ORDER BY nombre');
    }

    /**
     * Fragmento SQL + parámetros para filtrar por academia.
     * Uso:  [$filtro, $par] = Contexto::filtro('u');
     *       Db::todos("SELECT * FROM usuarios u WHERE $filtro", $par);
     */
    public static function filtro(string $alias = '', string $columna = 'academia_id'): array
    {
        $col = ($alias !== '' ? $alias . '.' : '') . $columna;
        $id  = self::academiaId();

        if ($id !== null)      return ["$col = :ctx_aca", ['ctx_aca' => $id]];
        if (Auth::esSuper())   return ['1=1', []];      // vista global del superadmin
        return ['1=0', []];                             // sin academia: no ve nada
    }

    /** Añade academia_id a los datos antes de insertar. */
    public static function marcar(array $datos): array
    {
        $datos['academia_id'] = self::exigirAcademia();
        return $datos;
    }
}
