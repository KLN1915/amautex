# Instalación de Amautex

Sistema en PHP 8 + MySQL 8. Probado en Laragon (Apache + PHP 8.3 + MySQL 8.4).

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

Crea la base `amautex`, sus 5 tablas y los accesos iniciales.

> El archivo empieza con `DROP DATABASE IF EXISTS amautex`: si ya tienes una
> base con ese nombre, la reemplaza.

Los archivos `01_` a `06_` son el histórico del esquema y sus migraciones.
Para una instalación nueva **no hay que ejecutarlos**.

## 3. Configurar la conexión

Editar `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'amautex');
define('DB_USER', 'root');
define('DB_PASS', '');          // poner la contraseña del MySQL local
define('APP_ENTORNO', 'local'); // 'produccion' en el servidor real
```

En `produccion` se ocultan los errores en pantalla y se registran en `logs/`.

## 4. Permisos de escritura

Las carpetas `logs/` y `uploads/` deben permitir escritura (en Windows suele
funcionar tal cual; en Linux: `chmod -R 775 logs uploads`).

## 5. Entrar

<http://localhost/amautex/>

| Acceso | Usuario | Clave | Qué ve |
|---|---|---|---|
| Dueño del sistema | `super` | `super123` | todas las academias, sus planes y credenciales |
| Academia Demo | `admin` | `admin123` | solo los datos de la Academia Demo |

**Cambiar ambas contraseñas en el primer ingreso** (menú lateral → *Mi contraseña*).

## 6. Opcional: librerías de terceros

Para PDF, Excel y correo:

```
composer install
```

Se instalan en `libreria/vendor/`. El sistema funciona sin esto; solo hace
falta cuando se agreguen reportes o envío de correos.

---

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
