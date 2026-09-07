<?php
/**
 * Precios (cuotas) de la academia.
 * Los montos viven en la fila de la academia; este módulo es el único
 * lugar donde se editan.
 */
defined('APP_NOMBRE') or exit;

require_once RUTA_FUNC . '/alumnos/alumnos_modelo.php';   // alumnos_tipos()

/** Los montos vigentes de la academia actual. */
function precios_obtener(): array
{
    $academiaId = Contexto::exigirAcademia();
    $a = Db::uno(
        'SELECT tarifa_estatal, tarifa_particular, tarifa_libre
           FROM academias WHERE id = :id',
        ['id' => $academiaId]
    );
    return [
        'estatal'    => (float) ($a['tarifa_estatal']    ?? 0),
        'particular' => (float) ($a['tarifa_particular'] ?? 0),
        'libre'      => (float) ($a['tarifa_libre']      ?? 0),
    ];
}

/**
 * Cuántos alumnos activos hay de cada tipo y cuánto suma su cuota.
 * Sirve para ver el efecto del cambio de precios antes de guardarlo.
 */
function precios_resumen(): array
{
    [$where, $params] = Contexto::filtro();
    $conteo = Db::todos(
        "SELECT tipo_alumno, COUNT(*) AS total
           FROM alumnos
          WHERE $where AND activo = 1
       GROUP BY tipo_alumno",
        $params
    );
    $porTipo = array_column($conteo, 'total', 'tipo_alumno');
    $precios = precios_obtener();

    $filas = [];
    $total = 0.0;
    foreach (alumnos_tipos() as $clave => $t) {
        $alumnos  = (int) ($porTipo[$clave] ?? 0);
        $subtotal = $alumnos * $precios[$clave];
        $total   += $subtotal;
        $filas[] = [
            'tipo'     => $clave,
            'titulo'   => $t['titulo'],
            'precio'   => $precios[$clave],
            'alumnos'  => $alumnos,
            'subtotal' => $subtotal,
        ];
    }
    return ['filas' => $filas, 'total' => $total];
}

/** Valida los montos del formulario. Devuelve [errores, datos]. */
function precios_validar(array $entrada): array
{
    $errores = [];
    $d = [];

    foreach (['estatal', 'particular', 'libre'] as $tipo) {
        $bruto = str_replace(',', '.', trim((string) ($entrada[$tipo] ?? '')));

        if ($bruto === '' || !is_numeric($bruto)) {
            $errores[] = 'El precio de "' . $tipo . '" debe ser un número.';
            continue;
        }
        $monto = round((float) $bruto, 2);
        if ($monto < 0)     $errores[] = 'El precio de "' . $tipo . '" no puede ser negativo.';
        if ($monto > 99999) $errores[] = 'El precio de "' . $tipo . '" es demasiado alto.';

        $d['tarifa_' . $tipo] = $monto;
    }

    return [$errores, $d];
}

function precios_guardar(array $datos): int
{
    $academiaId = Contexto::exigirAcademia();
    return Db::actualizar('academias', $datos, 'id = :id', ['id' => $academiaId]);
}
