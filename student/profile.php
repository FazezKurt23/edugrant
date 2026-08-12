<?php
/**
 * EduGrant — Student Profile (view + edit)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = getCurrentUser();
$me   = getStudentProfileByUserId($user['id']);
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('student/profile.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $first = clean($_POST['first_name'] ?? '');
        $middle = clean($_POST['middle_name'] ?? '');
        $last = clean($_POST['last_name'] ?? '');
        $studentId = clean($_POST['student_id'] ?? '');
        $birth = clean($_POST['birth_date'] ?? '');
        $gender = clean($_POST['gender'] ?? '');
        $school = clean($_POST['school'] ?? '');
        $course = clean($_POST['course'] ?? '');
        $yearLevel = clean($_POST['year_level'] ?? '');
        $address = clean($_POST['address'] ?? '');
        $city = clean($_POST['city'] ?? '');
        $province = clean($_POST['province'] ?? '');
        $contact = clean($_POST['contact_number'] ?? '');
        $gpa = clean($_POST['gpa'] ?? '');
        $income = clean($_POST['family_income'] ?? '');

        $errors = [];
        if ($first === '' || $last === '') {
            $errors[] = 'First name and last name are required.';
        }
        if ($studentId === '') {
            $errors[] = 'Student ID is required.';
        }
        if ($birth !== '' && !strtotime($birth)) {
            $errors[] = 'Birth date is invalid.';
        }
        if ($gpa !== '' && (!is_numeric($gpa) || (float)$gpa < 1.0 || (float)$gpa > 5.0)) {
            $errors[] = 'GPA must be a number between 1.00 and 5.00.';
        }
        if ($income !== '' && !is_numeric($income)) {
            $errors[] = 'Family income must be a number.';
        }
        if ($gender !== '' && !in_array($gender, ['male', 'female', 'other'], true)) {
            $errors[] = 'Gender is invalid.';
        }

        if (empty($errors)) {
            // Duplicate student_id check
            $chk = $pdo->prepare('SELECT id FROM student_profiles WHERE student_id = ? AND id != ?');
            $chk->execute([$studentId, $me['id']]);
            if ($chk->fetch()) {
                $errors[] = 'That Student ID is already used by another account.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'UPDATE student_profiles SET
                    student_id = ?, first_name = ?, middle_name = ?, last_name = ?,
                    birth_date = ?, gender = ?, school = ?, course = ?, year_level = ?,
                    address = ?, city = ?, province = ?, contact_number = ?, gpa = ?, family_income = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $studentId, $first, $middle, $last,
                $birth !== '' ? $birth : null, $gender !== '' ? $gender : null,
                $school, $course, $yearLevel,
                $address, $city, $province, $contact,
                $gpa !== '' ? $gpa : null, $income !== '' ? $income : null,
                $me['id'],
            ]);

            // Keep users.name in sync with first + last
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([trim($first . ' ' . $last), $user['id']]);
            $_SESSION['user_name'] = trim($first . ' ' . $last);

            logActivity($user['id'], 'Profile update', 'Student profile updated.');
            $_SESSION['flash_success'] = 'Profile updated successfully.';
            redirect('student/profile.php');
        } else {
            $_SESSION['flash_error'] = implode(' ', $errors);
            redirect('student/profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = (string)$_POST['current_password'] ?? '';
        $new = (string)$_POST['new_password'] ?? '';
        $confirm = (string)$_POST['confirm_password'] ?? '';

        $row = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $row->execute([$user['id']]);
        $hash = $row->fetchColumn();

        if (!password_verify($current, $hash)) {
            $_SESSION['flash_error'] = 'Your current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $_SESSION['flash_error'] = 'New passwords do not match.';
        } else {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            logActivity($user['id'], 'Password change', 'Password changed.');
            $_SESSION['flash_success'] = 'Password changed successfully.';
        }
        redirect('student/profile.php');
    }
}

$page_title = 'My Profile';
$layout = 'dashboard';
$page_active = 'profile';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-4">My Profile</h4>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header"><i class="bi bi-person me-1"></i>Personal Information</div>
                            <div class="card-body">
                                <form method="post">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label required">First name</label>
                                            <input type="text" class="form-control" name="first_name" value="<?php echo e($me['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Middle name</label>
                                            <input type="text" class="form-control" name="middle_name" value="<?php echo e($me['middle_name']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Last name</label>
                                            <input type="text" class="form-control" name="last_name" value="<?php echo e($me['last_name']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Student ID</label>
                                            <input type="text" class="form-control" name="student_id" value="<?php echo e($me['student_id']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Birth date</label>
                                            <input type="date" class="form-control" name="birth_date" value="<?php echo e($me['birth_date']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Gender</label>
                                            <select class="form-select" name="gender">
                                                <option value="">Select</option>
                                                <option value="male" <?php echo $me['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo $me['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="other" <?php echo $me['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">School</label>
                                            <input type="text" class="form-control" name="school" value="<?php echo e($me['school']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Course / Program</label>
                                            <input type="text" class="form-control" name="course" value="<?php echo e($me['course']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Year level</label>
                                            <select class="form-select" name="year_level">
                                                <option value="">Select</option>
                                                <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'] as $y): ?>
                                                    <option <?php echo $me['year_level'] === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">GPA</label>
                                            <input type="text" class="form-control" name="gpa" value="<?php echo e($me['gpa']); ?>" placeholder="e.g. 1.75">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Family income (PHP)</label>
                                            <input type="text" class="form-control" name="family_income" value="<?php echo e($me['family_income']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact number</label>
                                            <input type="text" class="form-control" name="contact_number" value="<?php echo e($me['contact_number']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" name="address" value="<?php echo e($me['address']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" name="city" value="<?php echo e($me['city']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Province</label>
                                            <input type="text" class="form-control" name="province" value="<?php echo e($me['province']); ?>">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary-soft mt-4"><i class="bi bi-save me-1"></i>Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header"><i class="bi bi-person-badge me-1"></i>Account</div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><strong>Email:</strong><br><?php echo e($user['email']); ?></li>
                                    <li class="mb-2"><strong>Role:</strong><br><span class="badge bg-primary">Student</span></li>
                                    <li><strong>Member since:</strong><br><?php echo e(formatDate($me['created_at'] ?? date('Y-m-d'), 'M j, Y')); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><i class="bi bi-shield-lock me-1"></i>Change Password</div>
                            <div class="card-body">
                                <form method="post">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label">Current password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New password</label>
                                        <input type="password" class="form-control" name="new_password" minlength="8" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm new password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-key me-1"></i>Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
