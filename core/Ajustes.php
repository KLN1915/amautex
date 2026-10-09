<?php
/**
 * Ajustes generales del sistema (tabla `configuracion`), comunes a todas las
 * academias. Solo el superadmin los cambia.
 *
 *   Ajustes::valor('omr_url')               -> texto o null
 *   Ajustes::secreto('omr_token')           -> texto descifrado o null
 *   Ajustes::guardar('omr_url', $url)
 *   Ajustes::guardar('omr_token', $t, true) -> se guarda cifrado
 *
 * Los secretos se cifran con libsodium (XSalsa20-Poly1305). La llave NO está
 * en la base: vive en config/clave_app.php (bloqueado por .htaccess y fuera
 * del repositorio). Así, un respaldo o una fuga de la base no revela el token.
 */
class Ajustes
{
    private const ARCHIVO_LLAVE = RAIZ . '/config/clave_app.php';
    private static ?array $cache = null;

    private static function todos(): array
    {
        if (self::$cache === null) {
            try {
                $filas = Db::todos('SELECT clave, valor, cifrado, actualizado_por, actualizado_en FROM configuracion');
            } catch (PDOException $e) {
                // tabla aún no creada (falta sql/07_configuracion.sql): se trabaja sin ajustes
                error_log('Ajustes: ' . $e->getMessage());
                $filas = [];
            }
            self::$cache = array_column($filas, null, 'clave');
        }
        return self::$cache;
    }

    public static function valor(string $clave): ?string
    {
        $f = self::todos()[$clave] ?? null;
        if (!$f || (int) $f['cifrado'] === 1) return null;     // los secretos se leen con secreto()
        return $f['valor'];
    }

    /** Valor secreto descifrado, o null si no hay o no se puede descifrar. */
    public static function secreto(string $clave): ?string
    {
        $f = self::todos()[$clave] ?? null;
        if (!$f || $f['valor'] === null || (int) $f['cifrado'] !== 1) return null;
        return self::descifrar($f['valor']);
    }

    /** ¿Hay un valor guardado para esa clave? (sin descifrarlo) */
    public static function existe(string $clave): bool
    {
        return isset(self::todos()[$clave]) && self::todos()[$clave]['valor'] !== null;
    }

    /** Quién y cuándo cambió el ajuste por última vez. */
    public static function meta(string $clave): ?array
    {
        $f = self::todos()[$clave] ?? null;
        if (!$f) return null;
        $nombre = $f['actualizado_por']
            ? Db::valor('SELECT nombre FROM usuarios WHERE id = :id', ['id' => $f['actualizado_por']])
            : null;
        return ['por' => $nombre, 'en' => $f['actualizado_en']];
    }

    public static function guardar(string $clave, ?string $valor, bool $cifrar = false): void
    {
        Db::run(
            'INSERT INTO configuracion (clave, valor, cifrado, actualizado_por)
                  VALUES (:c, :v, :x, :u)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), cifrado = VALUES(cifrado),
                                     actualizado_por = VALUES(actualizado_por)',
            [
                'c' => $clave,
                'v' => ($cifrar && $valor !== null) ? self::cifrar($valor) : $valor,
                'x' => $cifrar ? 1 : 0,
                'u' => Auth::logueado() ? Auth::id() : null,
            ]
        );
        self::$cache = null;
    }

    public static function borrar(string $clave): void
    {
        Db::eliminar('configuracion', 'clave = :c', ['c' => $clave]);
        self::$cache = null;
    }

    // ---------------------------------------------------------------- cifrado

    private static function llave(bool $crear = false): ?string
    {
        if (is_file(self::ARCHIVO_LLAVE)) {
            $b = base64_decode((string) require self::ARCHIVO_LLAVE, true);
            return ($b !== false && strlen($b) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) ? $b : null;
        }
        if (!$crear) return null;
        // Primera vez: se genera la llave al azar y se guarda en un archivo PHP
        // (si alguien lo pidiera por la web, solo vería una página vacía).
        $llave = sodium_crypto_secretbox_keygen();
        $php = "<?php\n// Llave de cifrado de los ajustes secretos. NO la borres ni la subas al repositorio:\n"
             . "// sin ella no se pueden leer los tokens guardados (habría que volver a ingresarlos).\n"
             . "return '" . base64_encode($llave) . "';\n";
        if (file_put_contents(self::ARCHIVO_LLAVE, $php, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo crear la llave de cifrado en config/. Revisa los permisos de la carpeta.');
        }
        @chmod(self::ARCHIVO_LLAVE, 0600);
        return $llave;
    }

    private static function cifrar(string $texto): string
    {
        $llave = self::llave(true);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($texto, $nonce, $llave));
    }

    private static function descifrar(string $guardado): ?string
    {
        $llave = self::llave();
        if (!$llave || !str_starts_with($guardado, 'v1:')) return null;
        $bin = base64_decode(substr($guardado, 3), true);
        if ($bin === false || strlen($bin) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return null;
        $nonce = substr($bin, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $texto = sodium_crypto_secretbox_open(substr($bin, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $llave);
        return $texto === false ? null : $texto;
    }

    /** ¿El secreto guardado se puede leer? (false = se perdió o cambió la llave) */
    public static function secretoLegible(string $clave): bool
    {
        return !self::existe($clave) || self::secreto($clave) !== null;
    }
}
