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
?>
<aside class="edu-sidebar" id="eduSidebar">
    <ul class="nav flex-column">
        <?php if ($currentUser['role'] === 'student'): ?>
            <?php
            echo sidebarItem('student/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $page_active);
            echo sidebarItem('student/scholarships.php', 'search', 'Find Scholarships', 'scholarships', $page_active);
            echo sidebarItem('student/saved-scholarships.php', 'bookmark-heart', 'Saved Scholarships', 'saved', $page_active);
            echo sidebarItem('student/applications.php', 'kanban', 'My Applications', 'applications', $page_active);
            echo sidebarItem('student/notifications.php', 'bell', 'Notifications', 'notifications', $page_active);
            echo sidebarItem('student/profile.php', 'person-circle', 'My Profile', 'profile', $page_active);
            ?>
        <?php elseif ($currentUser['role'] === 'provider'): ?>
            <?php
            echo sidebarItem('provider/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $page_active);
            echo sidebarItem('provider/scholarships.php', 'collection', 'My Scholarships', 'scholarships', $page_active);
            echo sidebarItem('provider/add-scholarship.php', 'plus-circle', 'Add Scholarship', 'add', $page_active);
            echo sidebarItem('provider/applications.php', 'kanban', 'Applications', 'applications', $page_active);
            echo sidebarItem('provider/notifications.php', 'bell', 'Notifications', 'notifications', $page_active);
            echo sidebarItem('provider/profile.php', 'building', 'Organization Profile', 'profile', $page_active);
            ?>
        <?php elseif ($currentUser['role'] === 'admin'): ?>
            <?php
            echo sidebarItem('admin/dashboard.php', 'speedometer2', 'Dashboard', 'dashboard', $page_active);
            echo sidebarItem('admin/users.php', 'people', 'All Users', 'users', $page_active);
            echo sidebarItem('admin/students.php', 'mortarboard', 'Students', 'students', $page_active);
            echo sidebarItem('admin/providers.php', 'buildings', 'Providers', 'providers', $page_active);
            echo sidebarItem('admin/scholarships.php', 'collection', 'Scholarships', 'scholarships', $page_active);
            echo sidebarItem('admin/pending-scholarships.php', 'hourglass-split', 'Pending Approvals', 'pending', $page_active);
            echo sidebarItem('admin/applications.php', 'kanban', 'Applications', 'applications', $page_active);
            echo sidebarItem('admin/notifications.php', 'bell', 'Notifications', 'notifications', $page_active);
            echo sidebarItem('admin/reports.php', 'bar-chart', 'Reports', 'reports', $page_active);
            ?>
        <?php endif; ?>
    </ul>
</aside>
