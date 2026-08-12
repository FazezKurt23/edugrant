<?php
/**
 * EduGrant — Registration (Students & Providers)
 * Admin registration is intentionally NOT available.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(getCurrentUser()['role'] . '/dashboard.php');
}

$error = '';
$old = $_POST ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('register.php');

    $role = $_POST['role'] ?? '';
    if (!in_array($role, ['student', 'provider'], true)) {
        $role = 'student';
    }

    $name     = clean($_POST['name'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    // Validation
    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($role === 'student' && clean($_POST['student_id'] ?? '') === '') {
        $error = 'Please provide your Student ID.';
    } elseif ($role === 'provider' && clean($_POST['organization_name'] ?? '') === '') {
        $error = 'Please provide your organization name.';
    }

    if ($error === '') {
        $pdo = db();
        // Duplicate email check
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$name, $email, $hash, $role]);
                $userId = (int)$pdo->lastInsertId();

                if ($role === 'student') {
                    $studentId   = clean($_POST['student_id'] ?? '');
                    $school      = clean($_POST['school'] ?? '');
                    $course      = clean($_POST['course'] ?? '');
                    $yearLevel   = clean($_POST['year_level'] ?? '');
                    $contact     = clean($_POST['contact_number'] ?? '');

                    $sp = $pdo->prepare(
                        'INSERT INTO student_profiles
                         (user_id, student_id, first_name, last_name, school, course, year_level, contact_number)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $sp->execute([$userId, $studentId, $name, '', $school, $course, $yearLevel, $contact]);
                    createNotification($userId, 'Welcome to ' . APP_NAME,
                        'Explore the scholarship directory and start tracking your applications.', 'system');
                } else {
                    $org      = clean($_POST['organization_name'] ?? '');
                    $contactP = clean($_POST['contact_person'] ?? '');
                    $contactN = clean($_POST['contact_number'] ?? '');
                    $address  = clean($_POST['address'] ?? '');
                    $website  = clean($_POST['website'] ?? '');

                    if (!validateUrl($website)) {
                        throw new RuntimeException('Website URL is invalid.');
                    }

                    $pp = $pdo->prepare(
                        'INSERT INTO provider_profiles
                         (user_id, organization_name, description, contact_person, contact_number, address, website, verification_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, "pending")'
                    );
                    $pp->execute([$userId, $org, '', $contactP, $contactN, $address, $website]);
                    createNotification($userId, 'Account created',
                        'Your provider account has been created. Await administrator verification.', 'system');
                    createNotification(1, 'New provider registered',
                        $org . ' registered and is awaiting verification.', 'system');
                }

                logActivity($userId, 'Registration', ucfirst($role) . ' account created.');

                $pdo->commit();

                $_SESSION['flash_success'] = 'Account created successfully. You can now log in.';
                redirect('login.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Registration error: ' . $e->getMessage());
                $error = 'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
$layout = 'auth';
include BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="auth-card card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h3 fw-bold text-navy mb-1">Create Your <?php echo e(APP_NAME); ?> Account</h1>
                <p class="text-muted mb-0">Students and providers can register. Admin accounts are system-managed.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo url('register.php'); ?>" novalidate>
                <?php echo csrfField(); ?>

                <div class="mb-3">
                    <label class="form-label">I am registering as</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="role" id="roleStudent" value="student"
                               <?php echo ($old['role'] ?? 'student') === 'student' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="roleStudent"><i class="bi bi-mortarboard me-1"></i>Student</label>
                        <input type="radio" class="btn-check" name="role" id="roleProvider" value="provider"
                               <?php echo ($old['role'] ?? '') === 'provider' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="roleProvider"><i class="bi bi-building me-1"></i>Provider</label>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="name" class="form-label required">Full name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo e($old['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label required">Email address</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo e($old['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label required">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               minlength="8" required>
                        <div class="form-text">At least 8 characters.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label required">Confirm password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>

                    <!-- Student-only fields -->
                    <div class="col-12" id="studentFields">
                        <hr class="my-2">
                        <h6 class="text-navy"><i class="bi bi-person-badge me-1"></i>Academic Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label required">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id"
                                       value="<?php echo e($old['student_id'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="year_level" class="form-label">Year level</label>
                                <select class="form-select" id="year_level" name="year_level">
                                    <option value="">Select year level</option>
                                    <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'] as $y): ?>
                                        <option <?php echo ($old['year_level'] ?? '') === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="school" class="form-label">School</label>
                                <input type="text" class="form-control" id="school" name="school"
                                       value="<?php echo e($old['school'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="course" class="form-label">Course / Program</label>
                                <input type="text" class="form-control" id="course" name="course"
                                       value="<?php echo e($old['course'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number"
                                       value="<?php echo e($old['contact_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Provider-only fields -->
                    <div class="col-12 d-none" id="providerFields">
                        <hr class="my-2">
                        <h6 class="text-navy"><i class="bi bi-building me-1"></i>Organization Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="organization_name" class="form-label required">Organization name</label>
                                <input type="text" class="form-control" id="organization_name" name="organization_name"
                                       value="<?php echo e($old['organization_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_person" class="form-label">Contact person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person"
                                       value="<?php echo e($old['contact_person'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number"
                                       value="<?php echo e($old['contact_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website"
                                       value="<?php echo e($old['website'] ?? ''); ?>" placeholder="https://...">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Organization address</label>
                                <input type="text" class="form-control" id="address" name="address"
                                       value="<?php echo e($old['address'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-soft w-100 py-2 fw-semibold mt-4">
                    <i class="bi bi-person-plus me-1"></i> Create Account
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted small mb-0">Already have an account?
                <a href="<?php echo url('login.php'); ?>">Log in</a>
            </p>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
