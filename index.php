<?php
/**
 * EduGrant — Landing Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();

// System statistics (real values from the database)
$totalScholarships = (int)$pdo->query("SELECT COUNT(*) FROM scholarships WHERE status='approved' AND (deadline IS NULL OR deadline >= CURDATE())")->fetchColumn();
$totalStudents     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalProviders    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='provider'")->fetchColumn();

// Featured scholarships (approved, not expired) — newest 4
$featuredStmt = $pdo->query(
    "SELECT s.*, p.organization_name
     FROM scholarships s
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE s.status = 'approved' AND (s.deadline IS NULL OR s.deadline >= CURDATE())
     ORDER BY s.created_at DESC
     LIMIT 4"
);
$featured = $featuredStmt->fetchAll();

// Upcoming deadlines (approved, not expired), soonest first — top 5
$deadlineStmt = $pdo->query(
    "SELECT s.*, p.organization_name
     FROM scholarships s
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE s.status = 'approved' AND s.deadline IS NOT NULL AND s.deadline >= CURDATE()
     ORDER BY s.deadline ASC
     LIMIT 5"
);
$upcomingDeadlines = $deadlineStmt->fetchAll();

$page_title = APP_NAME . ' — Find Opportunities. Build Your Future.';
$layout = 'public';
include BASE_PATH . '/includes/header.php';
?>

<!-- ================= HERO ================= -->
<section class="hero-section">
    <div class="container position-relative">
        <span class="hero-badge"><i class="bi bi-stars me-1"></i>BSIT Capstone Demo — <?php echo e(APP_NAME); ?></span>
        <h1>Discover Scholarships That Match Your Future</h1>
        <p class="lead mt-3">
            <?php echo e(APP_TAGLINE); ?> EduGrant helps students discover scholarship opportunities,
            understand eligibility requirements, track applications, and stay informed about important deadlines.
        </p>
        <div class="mt-4 d-flex flex-wrap gap-2">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-accent btn-lg px-4" href="<?php echo url(getCurrentUser()['role'] . '/dashboard.php'); ?>">
                    <i class="bi bi-speedometer2 me-1"></i> Go to Dashboard
                </a>
            <?php else: ?>
                <a class="btn btn-accent btn-lg px-4" href="<?php echo url('student/scholarships.php'); ?>">
                    <i class="bi bi-search me-1"></i> Find Scholarships
                </a>
                <a class="btn btn-outline-light btn-lg px-4" href="<?php echo url('register.php'); ?>">
                    Get Started <i class="bi bi-arrow-right ms-1"></i>
                </a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div><div class="num"><?php echo $totalScholarships; ?></div><div class="lbl">Active Scholarships</div></div>
            <div><div class="num"><?php echo $totalStudents; ?></div><div class="lbl">Student Users</div></div>
            <div><div class="num"><?php echo $totalProviders; ?></div><div class="lbl">Partner Providers</div></div>
        </div>
    </div>
</section>

<!-- ================= WHY EDGRANT ================= -->
<section id="why" class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">Why <?php echo e(APP_NAME); ?>?</h2>
            <p class="section-sub">Everything a student needs to find and manage scholarship opportunities.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card p-4">
                    <div class="feature-icon bg-blue-soft"><i class="bi bi-search text-primary"></i></div>
                    <h5 class="fw-bold">Smart Scholarship Search</h5>
                    <p class="text-muted mb-0">Search and filter real scholarship listings by type, education level, location, and deadline.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4">
                    <div class="feature-icon bg-green-soft"><i class="bi bi-clipboard-check text-success"></i></div>
                    <h5 class="fw-bold">Eligibility Guidance</h5>
                    <p class="text-muted mb-0">An honest eligibility guide that compares your profile with each scholarship&rsquo;s requirements.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4">
                    <div class="feature-icon bg-orange-soft"><i class="bi bi-alarm text-warning"></i></div>
                    <h5 class="fw-bold">Deadline Monitoring</h5>
                    <p class="text-muted mb-0">Track every deadline with automatic countdowns and reminder notifications.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4">
                    <div class="feature-icon bg-purple-soft"><i class="bi bi-kanban text-primary"></i></div>
                    <h5 class="fw-bold">Application Tracking</h5>
                    <p class="text-muted mb-0">Record your progress from interested to applied to under review — all in one place.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4">
                    <div class="feature-icon bg-blue-soft"><i class="bi bi-bookmark-heart text-primary"></i></div>
                    <h5 class="fw-bold">Saved Opportunities</h5>
                    <p class="text-muted mb-0">Bookmark scholarships you like and revisit them whenever you are ready.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4">
                    <div class="feature-icon bg-green-soft"><i class="bi bi-shield-check text-success"></i></div>
                    <h5 class="fw-bold">Secure &amp; Simple</h5>
                    <p class="text-muted mb-0">Password-hashed accounts, role-based access, and CSRF-protected forms throughout.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= HOW IT WORKS ================= -->
<section id="how" class="py-5" style="background:#fff;">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">How It Works</h2>
            <p class="section-sub">Three simple steps to start managing your scholarship journey.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="step-number mx-auto">1</div>
                <h5 class="fw-bold">Create Your Free Account</h5>
                <p class="text-muted">Register as a student in under a minute and complete your academic profile.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="step-number mx-auto">2</div>
                <h5 class="fw-bold">Discover &amp; Save Scholarships</h5>
                <p class="text-muted">Search the directory, check eligibility, save favorites, and track application progress.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="step-number mx-auto">3</div>
                <h5 class="fw-bold">Stay on Top of Deadlines</h5>
                <p class="text-muted">Receive deadline reminders and apply through the official scholarship website.</p>
            </div>
        </div>
    </div>
</section>

<!-- ================= FEATURED SCHOLARSHIPS ================= -->
<section id="scholarships" class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="section-title mb-0">Featured Scholarships</h2>
                <p class="section-sub mb-0">Hand-picked opportunities currently open for application.</p>
            </div>
            <a class="btn btn-primary-soft" href="<?php echo url('student/scholarships.php'); ?>">View All Scholarships <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <?php if (empty($featured)): ?>
            <div class="empty-state card">
                <i class="bi bi-inbox"></i>
                <p class="mt-2">No active scholarships available right now.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($featured as $s): $dl = getDeadlineState($s['deadline']); ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card scholar-card h-100">
                            <div class="card-body">
                                <span class="badge badge-status bg-<?php echo e($dl['badge']); ?> mb-2"><?php echo e($dl['label']); ?></span>
                                <h6 class="scholar-title"><?php echo e($s['title']); ?></h6>
                                <div class="provider-name mb-2"><i class="bi bi-building me-1"></i><?php echo e($s['organization_name']); ?></div>
                                <div class="meta-row mb-1"><i class="bi bi-mortarboard"></i><?php echo e($s['education_level'] ?: 'All levels'); ?></div>
                                <div class="meta-row mb-1"><i class="bi bi-geo-alt"></i><?php echo e($s['location'] ?: 'N/A'); ?></div>
                                <div class="meta-row mb-1"><i class="bi bi-cash-coin"></i><?php echo e($s['amount'] ?: 'Varies'); ?></div>
                                <div class="deadline-chip mt-2"><i class="bi bi-alarm"></i><?php echo e($dl['deadline']); ?></div>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3">
                                <a class="btn btn-primary-soft btn-sm w-100" href="<?php echo url('student/scholarship-details.php?id=' . $s['id']); ?>">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ================= UPCOMING DEADLINES ================= -->
<section class="py-5" style="background:#fff;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Upcoming Deadlines</h2>
            <span class="text-muted small">Countdowns are calculated automatically.</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Scholarship</th>
                        <th>Provider</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($upcomingDeadlines)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No upcoming deadlines right now.</td></tr>
                    <?php else: ?>
                        <?php foreach ($upcomingDeadlines as $s): $dl = getDeadlineState($s['deadline']); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo e($s['title']); ?></td>
                                <td><?php echo e($s['organization_name']); ?></td>
                                <td><?php echo e($dl['deadline']); ?></td>
                                <td>
                                    <span class="badge badge-status bg-<?php echo e($dl['badge']); ?>"><?php echo e($dl['label']); ?></span>
                                    <div class="small text-muted mt-1"><?php echo $dl['days_remaining'] >= 0 ? $dl['days_remaining'] . ' days remaining' : 'Closed'; ?></div>
                                </td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?php echo url('student/scholarship-details.php?id=' . $s['id']); ?>">Details</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ================= CTA ================= -->
<section class="py-5">
    <div class="container">
        <div class="cta-band text-center">
            <h2 class="fw-bold mb-2">Ready to find your next opportunity?</h2>
            <p class="mb-4" style="color:rgba(255,255,255,.85);">Create your free student account and start discovering scholarships today.</p>
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-accent btn-lg px-4" href="<?php echo url(getCurrentUser()['role'] . '/dashboard.php'); ?>">Go to My Dashboard</a>
            <?php else: ?>
                <a class="btn btn-accent btn-lg px-4" href="<?php echo url('register.php'); ?>">Create Free Account</a>
                <a class="btn btn-outline-light btn-lg px-4 ms-2" href="<?php echo url('login.php'); ?>">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
