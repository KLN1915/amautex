<?php defined('APP_NOMBRE') or exit; ?>
<?php
$fin  = $academia['fin_plan'] ?? null;
$dias = $fin ? (int) ((strtotime($fin) - strtotime(date('Y-m-d'))) / 86400) : null;
?>

<?php if ($dias !== null && $dias <= 15): ?>
    <div class="alert alert-<?= $dias < 0 ? 'danger' : 'warning' ?>">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= $dias < 0
            ? 'El plan de la academia venció el ' . fecha($fin) . '.'
            : 'El plan de la academia vence el ' . fecha($fin) . ' (faltan ' . $dias . ' días).' ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as $t): ?>
        <div class="col-12 col-sm-6 col-xl-4">
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

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel h-100">
            <div class="panel-cabecera"><h2>Últimos accesos</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Último acceso</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$ultimos): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">Sin registros todavía.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ultimos as $u): ?>
                            <tr>
                                <td><?= e($u['nombre']) ?></td>
                                <td><code><?= e($u['usuario']) ?></code></td>
                                <td><span class="badge text-bg-light"><?= e(ucfirst($u['rol'])) ?></span></td>
                                <td><?= fecha($u['ultimo_acceso'], 'd/m/Y H:i') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-cabecera"><h2>Plan contratado</h2></div>
            <div class="p-3">
                <dl class="lista-datos mb-0">
                    <dt>Academia</dt><dd><?= e($academia['nombre']) ?></dd>
                    <dt>Código</dt><dd><code><?= e($academia['codigo']) ?></code></dd>
                    <dt>Plan</dt><dd><span class="badge text-bg-light"><?= e(ucfirst($academia['plan'])) ?></span></dd>
                    <dt>Vence</dt><dd><?= $fin ? fecha($fin) : 'Sin vencimiento' ?></dd>
                    <dt>Usuarios</dt><dd><?= (int) $tarjetas[0]['valor'] ?> de <?= (int) $cupo ?></dd>
                    <dt>Alumnos</dt><dd><?= (int) $tarjetas[1]['valor'] ?> de <?= (int) $academia['limite_alumnos'] ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
