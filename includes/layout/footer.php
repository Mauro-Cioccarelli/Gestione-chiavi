<?php
/**
 * Layout - Footer HTML
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}
?>
    </main>
    
    <!-- Footer -->
    <footer class="mt-auto py-3 text-center text-muted small">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> v<?= APP_VERSION ?>
    </footer>
</div>
<!-- End Main Wrapper -->

<!-- Bootstrap JS -->
<script src="<?= asset('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>

<!-- Tabulator JS -->
<script src="<?= asset('tabulator-master/dist/js/tabulator.min.js') ?>"></script>

<!-- Tabulator Italian locale -->
<script>
Tabulator.registerModule("localize", "it", {
    "pagination": {
        "first": "Prima",
        "prev": "Precedente",
        "next": "Successiva",
        "last": "Ultima",
        "counter": {
            "showing": "Mostra",
            "of": "di",
            "rows": "righe",
            "all": "Tutte"
        }
    }
});
</script>

<!-- Custom JS -->
<script src="<?= asset('js/main.js') ?>"></script>

<!-- CSRF token per AJAX -->
<script>
window.CSRF_TOKEN = '<?= csrf_token() ?>';
window.APP_URL = '<?= APP_URL ?>';
</script>

<!-- Script aggiuntivi -->
<?php if (isset($extraJs) && is_array($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
