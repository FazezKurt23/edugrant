<?php
/**
 * EduGrant — Page Footer (Overhauled)
 *
 * Closes the dashboard wrapper, prints footer + scripts.
 * Uses $layout to decide whether to print the standard site footer.
 * Chart.js is only loaded when $loadChartJs is true (admin dashboard).
 */

$layout = $layout ?? 'public';
$currentUser = getCurrentUser();
$loadChartJs = $loadChartJs ?? false;
?>
</main>
<?php if (in_array($layout, ['public', 'auth'], true)): ?>
<footer class="edu-footer py-5 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h6 class="mb-3"><i class="bi bi-mortarboard-fill me-1"></i><?php echo e(APP_NAME); ?></h6>
                <p class="small" style="color:rgba(255,255,255,.7);"><?php echo e(APP_TAGLINE); ?> EduGrant connects students with scholarship opportunities and helps them manage their applications.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="mb-3">Explore</h6>
                <ul class="list-unstyled small d-grid gap-2">
                    <li><a href="<?php echo url('index.php'); ?>">Home</a></li>
                    <li><a href="<?php echo url('index.php#scholarships'); ?>">Scholarships</a></li>
                    <li><a href="<?php echo url('index.php#how'); ?>">How It Works</a></li>
                    <li><a href="<?php echo url('index.php#faq'); ?>">FAQ</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="mb-3">Get Started</h6>
                <ul class="list-unstyled small d-grid gap-2">
                    <li><a href="<?php echo url('register.php'); ?>">Create an Account</a></li>
                    <li><a href="<?php echo url('login.php'); ?>">Log In</a></li>
                    <li><a href="<?php echo url('student/scholarships.php'); ?>">Find Scholarships</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="mb-3">Why EduGrant?</h6>
                <p class="small" style="color:rgba(255,255,255,.7);">Smart search, eligibility guidance, deadline tracking, and application monitoring all in one secure platform.</p>
            </div>
        </div>
        <hr class="my-4" style="border-color:rgba(255,255,255,.12);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
            <span class="text-muted"><?php echo e(APP_NAME); ?> · &copy; <?php echo date('Y'); ?> · For educational purposes only.</span>
            <span class="text-muted"><i class="bi bi-mortarboard me-1"></i>Find Opportunities. Build Your Future.</span>
        </div>
    </div>
</footer>
<?php endif; ?>

<button class="scroll-top-btn" id="scrollTopBtn" aria-label="Back to top">
    <i class="bi bi-chevron-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($loadChartJs): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?php echo url('assets/js/app.js?v=' . (@filemtime(BASE_PATH . '/assets/js/app.js') ?: '1')); ?>"></script>
</body>
</html>
