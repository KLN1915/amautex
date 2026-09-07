<?php defined('APP_NOMBRE') or exit; ?>
<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as $t): ?>
        <div class="col-6 col-xl-3">
            <div class="tarjeta-kpi">
                <div class="icono text-<?= e($t['color']) ?>"><i class="bi <?= e($t['icono']) ?>"></i></div>
                <div>
                    <div class="valor"><?= number_format($t['valor']) ?></div>
                    <div class="etiqueta"><?= e($t['titulo']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="panel-cabecera">
        <h2>Próximos vencimientos de alquiler</h2>
        <a class="btn btn-sm btn-primary" href="<?= modulo('academias') ?>">
            <i class="bi bi-building me-1"></i>Gestionar academias
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Academia</th><th>Plan</th><th>Vence</th><th>Faltan</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php if (!$vencimientos): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Ninguna academia tiene fecha de vencimiento.</td></tr>
                <?php endif; ?>
                <?php foreach ($vencimientos as $v): $d = (int) $v['dias']; ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($v['nombre']) ?></div>
                            <small class="text-secondary"><?= e($v['codigo']) ?></small>
                        </td>
                        <td><span class="badge text-bg-light"><?= e(ucfirst($v['plan'])) ?></span></td>
                        <td><?= fecha($v['fin_plan']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $d < 0 ? 'danger' : ($d <= 15 ? 'warning' : 'success') ?>">
                                <?= $d < 0 ? 'vencida hace ' . abs($d) . ' d' : $d . ' días' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= $v['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                <?= e(ucfirst($v['estado'])) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-secondary small">
    <i class="bi bi-info-circle me-1"></i>
    Estás en la <strong>vista global</strong>. Para trabajar dentro de una academia, selecciónala en el menú lateral.
</p>
