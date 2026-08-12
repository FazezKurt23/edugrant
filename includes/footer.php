<?php
/**
 * EduGrant — Page Footer
 *
 * Closes the dashboard wrapper, prints footer + scripts.
 * Uses $layout to decide whether to print the standard site footer.
 */

$layout = $layout ?? 'public';
$currentUser = getCurrentUser();
?>
<footer class="edu-footer py-4 mt-auto">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span class="fw-bold"><i class="bi bi-mortarboard-fill me-1"></i><?php echo e(APP_NAME); ?></span>
                <p class="small text-muted mb-0 mt-1"><?php echo e(APP_TAGLINE); ?> — A BSIT Capstone Project demo application.</p>
            </div>
            <div class="col-md-6 text-center text-md-end small text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?> · For educational purposes only.
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?php echo url('assets/js/app.js'); ?>"></script>
</body>
</html>
