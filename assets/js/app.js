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

    // Register/Profile — academic level drives the year level list
    const academicLevel = document.getElementById('academic_level');
    const yearLevel = document.getElementById('year_level');

    function updateYearLevels() {
        if (!academicLevel || !yearLevel) return;
        const level = academicLevel.value;
        const all = ['Grade 11', 'Grade 12', '1st Year', '2nd Year', '3rd Year', '4th Year'];
        const current = yearLevel.value;
        const shs = ['Grade 11', 'Grade 12'];
        const col = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        const list = level === 'SHS' ? shs : level === 'College' ? col : all;
        yearLevel.innerHTML = '';
        const none = document.createElement('option');
        none.value = '';
        none.textContent = 'Select year level';
        yearLevel.appendChild(none);
        list.forEach(function (y) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            if (y === current) opt.selected = true;
            yearLevel.appendChild(opt);
        });
    }

    if (academicLevel) academicLevel.addEventListener('change', updateYearLevels);
    updateYearLevels();

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const close = alert.querySelector('.btn-close');
            if (close) close.click();
        }, 5000);
    });

    // Auto-hide chart.js tooltips are native; nothing extra needed.
});
