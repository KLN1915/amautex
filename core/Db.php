<?php
/**
 * Capa de acceso a datos: PDO singleton + atajos con consultas preparadas.
 * Nunca se concatena una variable dentro del SQL: siempre parámetros.
 */
class Db
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB: ' . $e->getMessage());
                if (APP_DEBUG) {
                    die('Conexión fallida: ' . $e->getMessage());
                }
                die('No se pudo conectar a la base de datos.');
            }
        }
        return self::$pdo;
    }

    /** Ejecuta y devuelve el statement (para casos especiales). */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /** Todas las filas. */
    public static function todos(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Una fila o null. */
    public static function uno(string $sql, array $params = []): ?array
    {
        $fila = self::run($sql, $params)->fetch();
        return $fila === false ? null : $fila;
    }

    /** Un solo valor escalar. */
    public static function valor(string $sql, array $params = [])
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** INSERT: devuelve el id generado. */
    public static function insertar(string $tabla, array $datos): int
    {
        $cols  = array_keys($datos);
        $marks = array_map(fn($c) => ':' . $c, $cols);
        $sql   = 'INSERT INTO `' . $tabla . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $marks) . ')';
        self::run($sql, $datos);
        return (int) self::conn()->lastInsertId();
    }

    /** UPDATE por condición: devuelve filas afectadas. */
    public static function actualizar(string $tabla, array $datos, string $where, array $paramsWhere = []): int
    {
        $sets = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($datos)));
        $sql  = "UPDATE `$tabla` SET $sets WHERE $where";
        return self::run($sql, array_merge($datos, $paramsWhere))->rowCount();
    }

    /** DELETE por condición: devuelve filas afectadas. */
    public static function eliminar(string $tabla, string $where, array $params = []): int
    {
        return self::run("DELETE FROM `$tabla` WHERE $where", $params)->rowCount();
    }

    /**
     * Arma un "(col1 LIKE :b0 OR col2 LIKE :b1 ...)" para buscadores.
     * Cada columna recibe SU PROPIO placeholder: con ATTR_EMULATE_PREPARES
     * desactivado, PDO no admite repetir el mismo nombre en una consulta.
     *
     *   [$sql, $par] = Db::like(['nombre', 'correo'], $texto);
     *   Db::todos("SELECT * FROM t WHERE $sql", $par);
     */
    public static function like(array $columnas, string $texto, string $prefijo = 'b'): array
    {
        $partes = [];
        $params = [];
        foreach (array_values($columnas) as $i => $col) {
            $clave = $prefijo . $i;
            $partes[] = $col . ' LIKE :' . $clave;
            $params[$clave] = '%' . $texto . '%';
        }
        return ['(' . implode(' OR ', $partes) . ')', $params];
    }

    // ---- Transacciones ----
    public static function iniciar(): void  { self::conn()->beginTransaction(); }
    public static function confirmar(): void { self::conn()->commit(); }
    public static function revertir(): void  { if (self::conn()->inTransaction()) self::conn()->rollBack(); }
}
