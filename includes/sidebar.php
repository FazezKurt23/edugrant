<?php
/**
 * EduGrant — Sidebar
 *
 * Renders the role-based sidebar for dashboard-style pages.
 * Requires $currentUser to be available (via getCurrentUser()).
 */

$currentUser = getCurrentUser();

function sidebarItem(string $href, string $icon, string $label, string $activeKey, string $currentKey): string
{
    $active = $currentKey === $activeKey ? ' active' : '';
    return '<li class="nav-item"><a class="nav-link' . $active . '" href="' . url($href) . '">' .
           '<i class="bi bi-' . $icon . ' me-2"></i>' . e($label) . '</a></li>';
}

function sidebarSection(string $label): string
{
    return '<li class="nav-item"><div class="sidebar-section-label">' . e($label) . '</div></li>';
}

$initials = '';
$nameParts = preg_split('/\s+/', trim($currentUser['name'] ?? ''));
if (!empty($nameParts[0])) {
    $initials = strtoupper(substr($nameParts[0], 0, 1));
    if (isset($nameParts[1])) {
        $initials .= strtoupper(substr($nameParts[1], 0, 1));
    }
}
?>
<aside class="edu-sidebar" id="eduSidebar">
    <div class="sidebar-profile">
        <div class="sidebar-avatar"><?php echo e($initials ?: 'U'); ?></div>
        <div class="overflow-hidden">
            <div class="sp-name"><?php echo e($currentUser['name']); ?></div>
            <div class="sp-role"><?php echo e($currentUser['role']); ?></div>
        </div>
    </div>
    <ul class="nav flex-column">
        <?php if ($currentUser['role'] === 'student'): ?>
            <?php
            echo sidebarSection('Main');
            echo sidebarItem('student/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $page_active);
            echo sidebarSection('Scholarships');
            echo sidebarItem('student/scholarships.php', 'search', 'Find Scholarships', 'scholarships', $page_active);
            echo sidebarItem('student/saved-scholarships.php', 'bookmark-heart', 'Saved Scholarships', 'saved', $page_active);
            echo sidebarItem('student/applications.php', 'kanban', 'My Applications', 'applications', $page_active);
            echo sidebarSection('Account');
            echo sidebarItem('student/notifications.php', 'bell', 'Notifications', 'notifications', $page_active);
            echo sidebarItem('student/profile.php', 'person-circle', 'My Profile', 'profile', $page_active);
            ?>
        <?php elseif ($currentUser['role'] === 'provider'): ?>
            <?php
            echo sidebarSection('Main');
            echo sidebarItem('provider/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $page_active);
            echo sidebarSection('Scholarships');
            echo sidebarItem('provider/scholarships.php', 'collection', 'My Scholarships', 'scholarships', $page_active);
            echo sidebarItem('provider/add-scholarship.php', 'plus-circle', 'Add Scholarship', 'add', $page_active);
            echo sidebarItem('provider/applications.php', 'kanban', 'Applications', 'applications', $page_active);
            echo sidebarSection('Account');
            echo sidebarItem('provider/notifications.php', 'bell', 'Notifications', 'notifications', $page_active);
            echo sidebarItem('provider/profile.php', 'building', 'Organization Profile', 'profile', $page_active);
            ?>
        <?php elseif ($currentUser['role'] === 'admin'): ?>
            <?php
            echo sidebarSection('Overview');
            echo sidebarItem('admin/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $page_active);
            echo sidebarSection('Management');
            echo sidebarItem('admin/users.php', 'people', 'All Users', 'users', $page_active);
            echo sidebarItem('admin/students.php', 'mortarboard', 'Students', 'students', $page_active);
            echo sidebarItem('admin/providers.php', 'buildings', 'Providers', 'providers', $page_active);
            echo sidebarItem('admin/scholarships.php', 'collection', 'Scholarships', 'scholarships', $page_active);
            echo sidebarItem('admin/pending-scholarships.php', 'hourglass-split', 'Pending Approvals', 'pending', $page_active);
            echo sidebarItem('admin/applications.php', 'kanban', 'Applications', 'applications', $page_active);
            echo sidebarSection('Insights');
            echo sidebarItem('admin/reports.php', 'bar-chart', 'Reports', 'reports', $page_active);
            echo sidebarItem('admin/notifications.php', 'bell', 'Notifications', 'notifications', $page_active);
            ?>
        <?php endif; ?>
    </ul>
</aside>
