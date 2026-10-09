<?php defined('APP_NOMBRE') or exit; ?>
<?php $fuentes = ['sistema' => 'guardado aquí (cifrado)', 'archivo' => 'config.local.php', 'ninguna' => '—']; ?>

<div class="row g-3">
    <!-- ================= Formulario ================= -->
    <div class="col-lg-7">
        <form class="panel" id="formLector" autocomplete="off">
            <div class="panel-cabecera">
                <h2><i class="bi bi-key me-2"></i>Conexión con el lector de fichas</h2>
            </div>

            <div class="p-3">
                <p class="text-secondary small">
                    <i class="bi bi-info-circle me-1"></i>
                    Una sola conexión para <strong>todas las academias</strong>: con este token el módulo
                    <em>Escanear fichas</em> envía las fotos al lector OMR del servidor.
                </p>

                <div class="mb-3">
                    <label class="form-label" for="url">URL de la API</label>
                    <input type="url" class="form-control font-monospace" id="url" name="url" required
                           maxlength="200" value="<?= e($estado['url']) ?>" placeholder="https://omr.midominio.com">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="token">Token</label>
                    <div class="input-group">
                        <input type="password" class="form-control font-monospace" id="token" name="token"
                               maxlength="130" spellcheck="false" autocomplete="new-password"
                               placeholder="<?= $estado['configurado'] ? 'Déjalo vacío para conservar el actual (…' . e($estado['final']) . ')' : 'omr_…' ?>">
                        <button class="btn btn-outline-secondary" type="button" id="btnVerToken" title="Mostrar / ocultar">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Se guarda cifrado y nunca se vuelve a mostrar completo.</div>
                </div>

                <div class="mb-1">
                    <label class="form-label" for="plantilla">Hoja por defecto</label>
                    <select class="form-select" id="plantilla" name="plantilla">
                        <option value="<?= e($estado['plantilla']) ?>"><?= e($estado['plantilla']) ?></option>
                    </select>
                    <div class="form-text">Al «Probar conexión» se cargan las hojas registradas en el lector.</div>
                </div>
            </div>

            <div class="panel-pie flex-wrap gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnQuitar"
                        <?= $estado['fuente'] === 'sistema' ? '' : 'hidden' ?>>
                    <i class="bi bi-trash me-1"></i>Quitar token
                </button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnProbar">
                        <i class="bi bi-plug me-1"></i>Probar conexión
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ================= Estado ================= -->
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-cabecera"><h2><i class="bi bi-activity me-2"></i>Estado</h2></div>
            <div class="p-3">
                <dl class="row small mb-0" id="estadoLector">
                    <dt class="col-5">Situación</dt>
                    <dd class="col-7" data-campo="situacion">
                        <?php if (!$estado['legible']): ?>
                            <span class="badge text-bg-danger">token ilegible</span>
                        <?php elseif ($estado['configurado']): ?>
                            <span class="badge text-bg-success">configurado</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">sin token</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-5">Token</dt>
                    <dd class="col-7 font-monospace" data-campo="final"><?= $estado['final'] ? '••••' . e($estado['final']) : '—' ?></dd>
                    <dt class="col-5">Origen</dt>
                    <dd class="col-7" data-campo="fuente"><?= e($fuentes[$estado['fuente']]) ?></dd>
                    <dt class="col-5">Hoja por defecto</dt>
                    <dd class="col-7" data-campo="plantilla"><?= e($estado['plantilla']) ?></dd>
                    <dt class="col-5">Última modificación</dt>
                    <dd class="col-7" data-campo="meta">
                        <?= $estado['meta'] ? e(fecha($estado['meta']['en'], 'd/m/Y H:i') . ' · ' . ($estado['meta']['por'] ?? '—')) : '—' ?>
                    </dd>
                </dl>
                <?php if (!$estado['legible']): ?>
                    <div class="alert alert-danger small mt-3 mb-0">
                        El token guardado no se puede descifrar (se perdió o cambió <code>config/clave_app.php</code>).
                        Vuelve a ingresarlo y guarda.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-cabecera"><h2><i class="bi bi-terminal me-2"></i>Crear o cambiar el token</h2></div>
            <div class="p-3 small">
                <p class="mb-2">En el servidor del lector:</p>
                <pre class="bg-body-tertiary border rounded p-2 small mb-2" style="white-space: pre-wrap">python api.py token crear amautex</pre>
                <p class="mb-2 text-secondary">
                    Se muestra una sola vez: pégalo aquí y guarda. Para dar de baja uno viejo:
                    <code>python api.py token revocar &lt;nombre&gt;</code>.
                </p>
                <p class="mb-0 text-secondary">
                    <i class="bi bi-shield-lock me-1"></i>
                    Solo el superadmin ve esta pantalla. Las academias usan el lector sin conocer el token.
                </p>
            </div>
        </div>
    </div>
</div>

<?php $scripts = ['lector.js']; ?>
