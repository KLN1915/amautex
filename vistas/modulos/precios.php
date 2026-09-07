<?php defined('APP_NOMBRE') or exit; ?>
<?php
// Un icono por tipo de alumno (Bootstrap Icons, servidos por CDN)
$iconos = [
    'estatal'    => 'bi-bank',
    'particular' => 'bi-building',
    'libre'      => 'bi-person-walking',
];
?>

<div class="row g-3">
    <div class="col-lg-7">
        <form class="panel h-100" id="formPrecios">
            <div class="panel-cabecera">
                <h2><i class="bi bi-cash-coin me-2"></i>Montos de inscripción</h2>
            </div>

            <div class="p-3">
                <p class="text-secondary small">
                    <i class="bi bi-info-circle me-1"></i>
                    Es el monto que le corresponde a cada alumno según su tipo de colegio.
                    Al registrar un ingreso, el sistema propone este importe.
                </p>

                <?php foreach ($tipos as $clave => $t): ?>
                    <div class="fila-precio">
                        <div class="fila-precio-nombre">
                            <i class="bi <?= e($iconos[$clave] ?? 'bi-tag') ?>"></i>
                            <div>
                                <div class="fw-semibold"><?= e($t['titulo']) ?></div>
                                <small class="text-secondary">
                                    <?= $clave === 'libre' ? 'No proviene de un colegio' : 'Alumno de colegio ' . e($clave) ?>
                                </small>
                            </div>
                        </div>
                        <div class="input-group input-group-sm fila-precio-monto">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.10" min="0" max="99999"
                                   class="form-control text-end"
                                   name="<?= e($clave) ?>"
                                   value="<?= number_format($precios[$clave], 2, '.', '') ?>"
                                   <?= $puedeEditar ? '' : 'disabled' ?>>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($puedeEditar): ?>
                <div class="panel-pie">
                    <span class="text-secondary small">
                        <i class="bi bi-clock-history me-1"></i>Cada cambio queda registrado en la bitácora.
                    </span>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Guardar precios
                    </button>
                </div>
            <?php else: ?>
                <div class="panel-pie">
                    <span class="text-secondary small">
                        <i class="bi bi-lock me-1"></i>Solo el administrador puede cambiar los precios.
                    </span>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-cabecera">
                <h2><i class="bi bi-calculator me-2"></i>Lo que suman los alumnos activos</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th class="text-center">Alumnos</th>
                            <th class="text-end">Cuota</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="tablaResumenPrecios">
                        <?php foreach ($resumen['filas'] as $f): ?>
                            <tr>
                                <td>
                                    <i class="bi <?= e($iconos[$f['tipo']] ?? 'bi-tag') ?> me-1 text-secondary"></i>
                                    <?= e($f['titulo']) ?>
                                </td>
                                <td class="text-center"><?= (int) $f['alumnos'] ?></td>
                                <td class="text-end"><?= moneda($f['precio']) ?></td>
                                <td class="text-end fw-semibold"><?= moneda($f['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fila-total">
                            <td colspan="3" class="text-end fw-semibold">Total por cobrar</td>
                            <td class="text-end fw-bold" id="totalPrecios"><?= moneda($resumen['total']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="panel-pie">
                <span class="text-secondary small">
                    <i class="bi bi-people me-1"></i>Solo cuenta alumnos activos.
                </span>
                <a class="btn btn-sm btn-light" href="<?= modulo('alumnos') ?>">
                    <i class="bi bi-mortarboard me-1"></i>Ver alumnos
                </a>
            </div>
        </div>
    </div>
</div>

<?php $scripts = ['precios.js']; ?>
