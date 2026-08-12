<?php
/**
 * EduGrant — Provider: Organization Profile
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('provider');

$user = getCurrentUser();
$me   = getProviderProfileByUserId($user['id']);
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('provider/profile.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $org = clean($_POST['organization_name'] ?? '');
        $desc = clean($_POST['description'] ?? '');
        $contactP = clean($_POST['contact_person'] ?? '');
        $contactN = clean($_POST['contact_number'] ?? '');
        $address = clean($_POST['address'] ?? '');
        $website = clean($_POST['website'] ?? '');

        $errors = [];
        if ($org === '') {
            $errors[] = 'Organization name is required.';
        }
        if ($website !== '' && !validateUrl($website)) {
            $errors[] = 'Website is not a valid URL.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'UPDATE provider_profiles SET organization_name = ?, description = ?, contact_person = ?,
                 contact_number = ?, address = ?, website = ? WHERE id = ?'
            );
            $stmt->execute([$org, $desc, $contactP, $contactN, $address, $website, $me['id']]);
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$org, $user['id']]);
            $_SESSION['user_name'] = $org;
            logActivity($user['id'], 'Profile update', 'Provider profile updated.');
            $_SESSION['flash_success'] = 'Organization profile updated.';
        } else {
            $_SESSION['flash_error'] = implode(' ', $errors);
        }
        redirect('provider/profile.php');
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
        redirect('provider/profile.php');
    }
}

$verifBadge = ['pending' => 'warning text-dark', 'verified' => 'success', 'rejected' => 'danger'];
$verifNote = [
    'pending' => 'Your organization is awaiting administrator verification. You can still create and submit scholarships while unverified.',
    'verified' => 'Your organization has been verified by the administrator.',
    'rejected' => 'Your organization verification was rejected. Please contact the administrator.',
];

$page_title = 'Organization Profile';
$layout = 'dashboard';
$page_active = 'profile';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-4">Organization Profile</h4>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header"><i class="bi bi-building me-1"></i>Organization Information</div>
                            <div class="card-body">
                                <form method="post">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label required">Organization name</label>
                                            <input type="text" class="form-control" name="organization_name" value="<?php echo e($me['organization_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Website</label>
                                            <input type="url" class="form-control" name="website" value="<?php echo e($me['website']); ?>" placeholder="https://...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact person</label>
                                            <input type="text" class="form-control" name="contact_person" value="<?php echo e($me['contact_person']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact number</label>
                                            <input type="text" class="form-control" name="contact_number" value="<?php echo e($me['contact_number']); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" name="address" value="<?php echo e($me['address']); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="4"><?php echo e($me['description']); ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary-soft mt-4"><i class="bi bi-save me-1"></i>Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header"><i class="bi bi-patch-check me-1"></i>Verification Status</div>
                            <div class="card-body">
                                <span class="badge badge-status bg-<?php echo e($verifBadge[$me['verification_status']] ?? 'secondary'); ?> mb-2">
                                    <?php echo ucfirst($me['verification_status']); ?>
                                </span>
                                <p class="small text-muted mb-0"><?php echo e($verifNote[$me['verification_status']] ?? ''); ?></p>
                                <hr>
                                <div class="small text-muted mb-1"><strong>Account email:</strong><br><?php echo e($user['email']); ?></div>
                                <div class="small text-muted mb-1"><strong>Member since:</strong><br><?php echo e(formatDate($me['created_at'], 'M j, Y')); ?></div>
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
