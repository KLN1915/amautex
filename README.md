# Amautex · sistema multi-academia

Sistema PHP pensado para **alquilarse a varias academias**. Cada academia ve
únicamente sus datos; el dueño del sistema (superadmin) administra las
academias, sus planes y sus credenciales.

## El modelo: una sola instalación, muchas academias

```
                         ┌──────────────────────────────┐
                         │  superadmin (academia = NULL) │
                         │  gestiona academias y claves  │
                         └───────────────┬──────────────┘
                                         │
        ┌────────────────────┬───────────┴────────┬────────────────────┐
   Academia 1           Academia 2           Academia 3           Academia N
   admin/operador…      admin/operador…      admin/operador…      …
   alumnos, datos…      alumnos, datos…      alumnos, datos…      …
        └── cada fila lleva academia_id: esa columna es la frontera ──┘
```

- **Una sola base de datos**, con `academia_id` en cada tabla operativa.
- El **login es único en todo el sistema** (`usuarios.usuario`), así el usuario
  no tiene que escribir el código de su academia al ingresar.
- Al entrar, la sesión guarda su academia. `Contexto::academiaId()` la devuelve
  y **ignora cualquier manipulación de la sesión o la URL**: un admin siempre
  queda encerrado en su academia.
- El superadmin puede **pararse en una academia** (selector del menú lateral)
  o quedarse en **vista global**.

### Roles

| Rol | Alcance | Puede |
|---|---|---|
| `superadmin` | todo el sistema | crear academias, suspender, renovar planes, resetear credenciales, entrar a cualquier academia |
| `admin` | su academia | gestionar usuarios, alumnos, precios y toda la operación |
| `operador` | su academia | cargar y editar datos |
| `consulta` | su academia | solo lectura |

## Árbol de carpetas

```
amautex/
├── index.php                 ← ÚNICA puerta de entrada (front controller)
├── .htaccess                 ← bloquea el acceso directo a carpetas internas
│
├── config/
│   ├── config.php            ← BD, rutas, entorno, zona horaria
│   └── rutas.php             ← módulo → controlador → roles → menú
│
├── core/                     ← el motor (no se toca al agregar módulos)
│   ├── bootstrap.php         ← config + autoload + sesión + helpers
│   ├── Db.php                ← PDO + consultas preparadas + transacciones
│   ├── Auth.php              ← login, roles, plan vencido / academia suspendida
│   ├── Contexto.php          ← ★ academia actual y filtro multi-academia
│   ├── Seguridad.php         ← CSRF, escape, saneo
│   ├── Vista.php             ← render con layout
│   ├── Respuesta.php         ← JSON {ok, mensaje, datos}
│   └── helpers.php           ← e(), modulo(), academia_id(), bitacora()…
│
├── funciones/                ← LÓGICA por módulo
│   ├── auth/{login,salir,clave}.php   clave = cambio de contraseña propia
│   ├── academias/            ← ★ administración del negocio (solo superadmin)
│   │   ├── academias.php         alta, plan, suspensión, renovación, credenciales
│   │   └── academias_modelo.php
│   ├── alumnos/                  registro de alumnos de la academia
│   ├── precios/                  cuotas por tipo de alumno
│   ├── dashboard/dashboard.php   panel global o panel de la academia
│   └── usuarios/                 CRUD de usuarios DENTRO de la academia
│
├── vistas/                   ← SOLO HTML
│   ├── layout/{header,sidebar,footer}.php   sidebar con selector de academia
│   ├── modulos/{login,clave,dashboard,dashboard_sistema,alumnos,precios,usuarios,academias}.php
│   └── errores/{403,404,500}.php
│
├── assets/                   ← DISEÑO
│   ├── css/estilos.css       ← el color de marca sale de la academia actual
│   └── js/{app.js,alumnos.js,precios.js,usuarios.js,academias.js}
│
├── libreria/vendor/          ← composer (FPDF, PhpSpreadsheet, PHPMailer…)
├── sql/                      ← esquema, datos iniciales y migración
├── uploads/  logs/
```

## Cómo circula una petición

```
Navegador
   │  index.php?p=usuarios&accion=guardar
   ▼
index.php ──► config/rutas.php   ¿existe el módulo? ¿el rol tiene acceso?
   │          Seguridad::verificar()   CSRF en todo POST
   ▼
funciones/usuarios/usuarios.php
   │          Contexto::academiaId()   ← en qué academia estamos
   │   ├── sin accion  ──► Vista::render()
   │   └── con accion  ──► modelo (filtrado por academia) ──► Db
   ▼
JSON {ok, mensaje, datos} ──► assets/js pinta la pantalla
```

## Puesta en marcha

1. Importar `sql/01_esquema.sql` y `sql/02_datos_iniciales.sql`.
   *(Si ya habías importado versiones anteriores, ejecutar en su lugar
   `sql/03_` … `sql/06_` en orden.)*
2. Ajustar `config/config.php` (DB_NAME, DB_USER, DB_PASS, APP_ENTORNO).
3. Ingresar y cambiar ambas contraseñas:

   | Acceso | Usuario | Clave | Ve |
   |---|---|---|---|
   | Dueño del sistema | `super` | `super123` | todas las academias |
   | Academia Demo | `admin` | `admin123` | solo Academia Demo |

4. Opcional: `composer install` para las librerías de `libreria/vendor/`.

## Alquilar el sistema a una academia nueva

1. Entrar como `super` → **Academias** → *Nueva academia*.
2. Llenar datos, plan, vencimiento y límites (usuarios / alumnos).
3. En el mismo formulario se define el **administrador del cliente**; si dejas
   la contraseña vacía, el sistema **genera una y la muestra una sola vez**.
   Esa clave queda marcada como provisional: al ingresar, el cliente está
   **obligado a cambiarla** antes de poder usar el sistema (lo mismo pasa
   cuando restableces una credencial desde `🔑`).
4. Desde la lista de academias: `⟳` renovar meses de alquiler, la insignia de
   estado suspende o reactiva, `🔑` ver y restablecer credenciales,
   `→` entrar a esa academia para ver el sistema como lo ve el cliente.

Si el plan vence o la academia queda suspendida, **sus usuarios no pueden
ingresar** y el login les explica el motivo. El superadmin nunca se bloquea.

## Módulo de Alumnos

Cada alumno pertenece a una academia y guarda:

| Campo | Detalle |
|---|---|
| **Código** | correlativo por academia (`A0001`); si lo dejas vacío se genera solo |
| **Apellido paterno** | obligatorio |
| **Apellido materno** | opcional (hay alumnos con un solo apellido) |
| **Nombres** | obligatorio; se listan como “PATERNO MATERNO, Nombres” y se ordenan por paterno → materno → nombres |
| **Tipo** | Estatal · Particular · **Alumno libre** — define la cuota |
| **Colegio** | combobox del catálogo de la academia, filtrado por el tipo elegido; obligatorio salvo alumno libre |
| **Nivel** | inicial · primaria · secundaria · otro/egresado |
| **Grado** | depende del nivel: inicial 3-5 años, primaria 1.º-6.º, secundaria 1.º-5.º |

### Tipo de alumno y cuota

El tipo decide cuánto paga, con los montos configurados **por academia** en el
módulo **Precios** (menú lateral):

| Tipo | Cuota por defecto |
|---|---|
| Colegio estatal | S/ 12.00 |
| Colegio particular | S/ 14.00 |
| Alumno libre | S/ 14.00 |

- El combobox de colegios sale de la tabla `colegios` (catálogo por academia).
  Con «➕ Agregar colegio nuevo…» se registra uno sin salir del formulario.
- El servidor comprueba que el colegio elegido **sea del tipo marcado**: no se
  puede guardar un alumno “estatal” apuntando a un colegio particular.
- El alumno libre no lleva colegio.

- Los grados válidos se definen **una sola vez** en `alumnos_niveles()`
  (`funciones/alumnos/alumnos_modelo.php`); el formulario los recibe en JSON,
  así el `<select>` de grado se arma solo al cambiar de nivel y el servidor
  vuelve a validar la combinación.
- El código es **único dentro de la academia**, no en todo el sistema.
- Al registrar se respeta el **límite de alumnos del plan** de la academia.
- Filtros de la pantalla: tipo, colegio, nivel, grado y búsqueda por código,
  nombre o colegio; abajo, el resumen por nivel/grado y las cuotas vigentes.

## Módulo de Precios

Único lugar donde se editan las cuotas de la academia. Lo usan el `admin` de la
academia y el `superadmin` (parado en esa academia).

- Tres montos: colegio estatal, colegio particular y alumno libre.
- Al costado, cuántos alumnos activos hay de cada tipo y **cuánto suman**, para
  ver el efecto de un cambio de precio antes de aplicarlo.
- Cada cambio queda registrado en la bitácora (valores anterior y nuevo).

## Iconos

Todos los iconos salen de **Bootstrap Icons por CDN** (`<i class="bi bi-...">`).
No se usan emojis sueltos en la interfaz: se ven distinto en cada equipo y no
se pueden colorear ni alinear con el texto.

## Agregar otro módulo (matrículas, pagos, asistencia…)

Copia el patrón de `funciones/alumnos/`:

1. `config/rutas.php` → nueva entrada con roles, menú y grupo.
2. `funciones/<modulo>/<modulo>_modelo.php`:

   ```php
   [$filtro, $params] = Contexto::filtro();          // ← SIEMPRE
   Db::todos("SELECT * FROM matriculas WHERE $filtro", $params);

   Db::insertar('matriculas', Contexto::marcar($datos)); // agrega academia_id
   ```
3. `funciones/<modulo>/<modulo>.php` → `switch ($accion)`.
4. `vistas/modulos/<modulo>.php` + `assets/js/<modulo>.js`.

## Reglas que sostienen el orden

- **Toda tabla operativa lleva `academia_id`** y **toda consulta usa
  `Contexto::filtro()`**. Es lo único que separa a una academia de otra.
- Nunca SQL concatenado: `Db::run($sql, ['id' => $id])`.
- Para buscadores, `Db::like(['col1','col2'], $texto)`: PDO en modo nativo
  **no admite repetir un placeholder**, y el helper le da uno propio a cada columna.
- Nada de HTML en `funciones/` ni consultas en `vistas/`.
- Todo lo que se imprime pasa por `e()`; todo POST lleva token CSRF.
- Contraseñas con `password_hash()`; las claves generadas se muestran una vez.
- Los endpoints AJAX devuelven siempre `{ok, mensaje, datos}`.
