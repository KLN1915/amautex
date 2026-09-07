<?php
/**
 * Módulo: panel principal.
 * Muestra dos paneles distintos:
 *   - Superadmin sin academia seleccionada -> resumen del negocio (todas las academias).
 *   - Dentro de una academia               -> resumen de esa academia.
 */
defined('APP_NOMBRE') or exit;

$academiaId = academia_id();

if ($academiaId === null && es_super()) {
    // ---------- Vista global del dueño del sistema ----------
    $tarjetas = [
        ['titulo' => 'Academias activas',  'valor' => (int) Db::valor("SELECT COUNT(*) FROM academias WHERE estado = 'activa'"),      'icono' => 'bi-building-check', 'color' => 'primary'],
        ['titulo' => 'Suspendidas',        'valor' => (int) Db::valor("SELECT COUNT(*) FROM academias WHERE estado = 'suspendida'"),  'icono' => 'bi-building-slash', 'color' => 'secondary'],
        ['titulo' => 'Por vencer (30 días)','valor' => (int) Db::valor('SELECT COUNT(*) FROM academias WHERE fin_plan IS NOT NULL AND fin_plan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'), 'icono' => 'bi-hourglass-split', 'color' => 'warning'],
        ['titulo' => 'Vencidas',           'valor' => (int) Db::valor('SELECT COUNT(*) FROM academias WHERE fin_plan IS NOT NULL AND fin_plan < CURDATE()'), 'icono' => 'bi-exclamation-octagon', 'color' => 'danger'],
    ];

    $vencimientos = Db::todos(
        'SELECT id, nombre, codigo, plan, estado, fin_plan,
                DATEDIFF(fin_plan, CURDATE()) AS dias
           FROM academias
          WHERE fin_plan IS NOT NULL
       ORDER BY fin_plan
          LIMIT 8'
    );

    Vista::render('dashboard_sistema', compact('tarjetas', 'vencimientos'), 'Panel del sistema');
}

// ---------- Vista de una academia ----------
Contexto::exigirAcademia();
$academia = Contexto::academia();
$cupo     = (int) $academia['limite_usuarios'];

$tarjetas = [
    ['titulo' => 'Usuarios activos', 'valor' => (int) Db::valor('SELECT COUNT(*) FROM usuarios WHERE academia_id = :a AND activo = 1', ['a' => $academiaId]), 'icono' => 'bi-people',        'color' => 'primary'],
    ['titulo' => 'Alumnos',          'valor' => (int) Db::valor('SELECT COUNT(*) FROM alumnos  WHERE academia_id = :a AND activo = 1', ['a' => $academiaId]), 'icono' => 'bi-mortarboard',   'color' => 'success'],
    ['titulo' => 'Accesos hoy',      'valor' => (int) Db::valor('SELECT COUNT(*) FROM usuarios WHERE academia_id = :a AND DATE(ultimo_acceso) = CURDATE()', ['a' => $academiaId]), 'icono' => 'bi-box-arrow-in-right', 'color' => 'secondary'],
];

$ultimos = Db::todos(
    'SELECT nombre, usuario, rol, ultimo_acceso
       FROM usuarios
      WHERE academia_id = :a AND ultimo_acceso IS NOT NULL
   ORDER BY ultimo_acceso DESC
      LIMIT 8',
    ['a' => $academiaId]
);

Vista::render('dashboard', compact('tarjetas', 'ultimos', 'academia', 'cupo'), 'Panel · ' . $academia['nombre']);
