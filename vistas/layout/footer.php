<?php defined('APP_NOMBRE') or exit; ?>
    </div><!-- /cuerpo -->
    <footer class="pie">
        <?= e(APP_NOMBRE) ?> · <?= date('Y') ?>
        <?php if (APP_DEBUG): ?><span class="badge text-bg-warning ms-2">modo local</span><?php endif; ?>
    </footer>
</main>
</div><!-- /app -->

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="avisos"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>

<?php /* Librerías externas del módulo. Van antes que sus scripts, que las usan. */ ?>
<?php if (!empty($libs)) foreach ((array) $libs as $lib): ?>
<script src="<?= e($lib) ?>"></script>
<?php endforeach; ?>

<?php if (!empty($scripts)) foreach ((array) $scripts as $s): ?>
<script src="<?= asset('js/' . $s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
