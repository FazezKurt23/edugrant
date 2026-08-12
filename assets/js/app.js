/**
 * EduGrant — Global Front-End Behavior
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('eduSidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // Register role toggle — show/hide student vs provider fields
    const roleStudent = document.getElementById('roleStudent');
    const roleProvider = document.getElementById('roleProvider');
    const studentFields = document.getElementById('studentFields');
    const providerFields = document.getElementById('providerFields');

    function updateRoleFields() {
        const isProvider = roleProvider && roleProvider.checked;
        if (studentFields) studentFields.classList.toggle('d-none', isProvider);
        if (providerFields) providerFields.classList.toggle('d-none', !isProvider);
    }

    if (roleStudent) roleStudent.addEventListener('change', updateRoleFields);
    if (roleProvider) roleProvider.addEventListener('change', updateRoleFields);
    updateRoleFields();

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const close = alert.querySelector('.btn-close');
            if (close) close.click();
        }, 5000);
    });

    // Auto-hide chart.js tooltips are native; nothing extra needed.
});
