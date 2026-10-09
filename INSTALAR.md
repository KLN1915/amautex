# Instalación de Amautex

Sistema en PHP 8 + MySQL 8. Probado en Laragon (Apache + PHP 8.3 + MySQL 8.4).
Necesita las extensiones `pdo_mysql`, `curl`, `fileinfo` y `sodium` (vienen con PHP 8).

> ## ¿Ya tienes Amautex funcionando en el servidor? → ve a «Actualizar» (abajo)
> **No importes `00_base_completa.sql` sobre una base con datos: la borra entera.**

## 1. Copiar los archivos

Descomprimir la carpeta `amautex` dentro del directorio web del servidor:

- **Laragon:** `C:\laragon\www\amautex`
- **XAMPP:** `C:\xampp\htdocs\amautex`

## 2. Crear la base de datos

Importar **un solo archivo**: `sql/00_base_completa.sql`

- Por phpMyAdmin: *Importar* → elegir el archivo → Continuar.
- Por consola:

  ```
  mysql -u root -p < sql/00_base_completa.sql
  ```

Crea la base `amautex`, sus 8 tablas y los accesos iniciales.

> El archivo empieza con `DROP DATABASE IF EXISTS amautex`: si ya tienes una
> base con ese nombre, la reemplaza.

Los archivos `01_` a `08_` son el histórico del esquema y sus migraciones.
Para una instalación nueva **no hay que ejecutarlos**.

## 3. Configurar la conexión

Copiar `config/config.local.php.example` como `config/config.local.php` y poner
los datos de ese servidor (no se edita `config/config.php`):

```php
define('APP_ENTORNO', 'produccion');   // en tu PC puedes omitirlo (queda 'local')
define('DB_HOST', 'localhost');
define('DB_NAME', 'amautex');
define('DB_USER', 'usuario_de_la_base');
define('DB_PASS', 'contraseña');
```

Sin `config.local.php` se usan los valores de un Laragon recién instalado
(`root` sin contraseña). En `produccion` se ocultan los errores en pantalla y se
registran en `logs/`.

## 4. Permisos de escritura

Las carpetas `logs/` y `uploads/` deben permitir escritura (en Windows suele
funcionar tal cual; en Linux: `chmod -R 775 logs uploads`). La carpeta `config/`
también, **solo la primera vez** que el superadmin guarda el token del lector: ahí se
crea `config/clave_app.php` (la llave de cifrado). Después puede volver a solo lectura.

## 5. Entrar

<http://localhost/amautex/>

| Acceso | Usuario | Clave | Qué ve |
|---|---|---|---|
| Dueño del sistema | `super` | `super123` | todas las academias, sus planes y credenciales |
| Academia Demo | `admin` | `admin123` | solo los datos de la Academia Demo |

**Cambiar ambas contraseñas en el primer ingreso** (menú lateral → *Mi contraseña*).

## 6. Lector de fichas ópticas

Entrar como `super` → **Administración › Lector de fichas** → pegar el token de la API →
**Probar conexión** → **Guardar**. Vale para todas las academias.

## 7. Opcional: librerías de terceros

Para PDF, Excel y correo:

```
composer install
```

Se instalan en `libreria/vendor/`. El sistema funciona sin esto; solo hace
falta cuando se agreguen reportes o envío de correos.

---

## Actualizar un servidor que ya tiene Amautex

1. **Respaldo** de la base (phpMyAdmin → Exportar) y de la carpeta actual.
2. Subir los archivos del ZIP **encima** de los existentes. No borres en el servidor:
   - `config/config.local.php` (si ya lo tenías), `logs/` ni `uploads/` (ahí quedan las fichas escaneadas).
   - Si tu servidor tenía los datos de la base escritos en `config/config.php`,
     pásalos primero a `config/config.local.php` (ver el paso 3), porque el
     `config.php` nuevo trae los valores de Laragon.
3. Ejecutar **solo** las migraciones que falten, en orden. Para esta versión:
   - `sql/07_configuracion.sql`: tabla `configuracion` (token del lector).
   - `sql/08_examenes.sql`: tablas `examenes` y `examen_resultados`, y las reglas de
     puntaje en `academias` (+10 / −2 / 0 por defecto). No borra ni cambia datos.

   Cada una se ejecuta **una sola vez** (si la repites, MySQL avisa que la tabla o la
   columna ya existe: no pasa nada).
4. Entrar como `super` y cargar el token en **Administración › Lector de fichas**.
5. Probar **Escanear fichas** desde una academia.

> El servidor necesita **Apache con `mod_rewrite`** para que `.htaccess` bloquee las
> carpetas internas (`config/`, `core/`, `sql/`, `logs/`…). Si usa **Nginx**, hay que
> negar esas rutas en su configuración; si no, quedarían accesibles desde la web.

## Cómo está organizado el código

Lee `README.md`: explica la estructura de carpetas, cómo circula una petición
y cómo agregar un módulo nuevo.

Lo más importante para no romper nada:

- **Todo entra por `index.php`** (`index.php?p=modulo&accion=...`). No se crean
  archivos PHP sueltos accesibles desde el navegador.
- **Toda tabla operativa lleva `academia_id`** y toda consulta usa
  `Contexto::filtro()`. Eso es lo único que impide que una academia vea los
  datos de otra.
- Nunca concatenar variables en el SQL: `Db::run($sql, ['id' => $id])`.
- Para buscadores usar `Db::like(['col1','col2'], $texto)`.
- Todo lo que se imprime en HTML pasa por `e()`.
