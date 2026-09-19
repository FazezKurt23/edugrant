/**
 * EduGrant — Global Front-End Behavior (Overhauled)
 */

document.addEventListener('DOMContentLoaded', function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const safeAnimate = prefersReducedMotion;

    // ===== Mobile sidebar toggle =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('eduSidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (sidebar.classList.contains('show') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    // ===== Register role toggle =====
    const roleStudent = document.getElementById('roleStudent');
    const roleProvider = document.getElementById('roleProvider');
    const studentFields = document.getElementById('studentFields');
    const providerFields = document.getElementById('providerFields');

    function updateRoleFields() {
        const isProvider = roleProvider && roleProvider.checked;
        if (studentFields) {
            studentFields.classList.toggle('d-none', isProvider);
            studentFields.querySelectorAll('input').forEach(function (el) { el.disabled = isProvider; });
        }
        if (providerFields) {
            providerFields.classList.toggle('d-none', !isProvider);
            providerFields.querySelectorAll('input').forEach(function (el) { el.disabled = !isProvider; });
        }
    }

    if (roleStudent) roleStudent.addEventListener('change', updateRoleFields);
    if (roleProvider) roleProvider.addEventListener('change', updateRoleFields);
    updateRoleFields();

    // ===== Academic level drives year level list =====
    const academicLevel = document.getElementById('academic_level');
    const yearLevel = document.getElementById('year_level');

    function updateYearLevels() {
        if (!academicLevel || !yearLevel) return;
        const level = academicLevel.value;
        const current = yearLevel.value;
        const shs = ['Grade 11', 'Grade 12'];
        const col = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        const list = level === 'SHS' ? shs : level === 'College' ? col : shs.concat(col);
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

    // ===== Password visibility toggle =====
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword
                ? '<i class="bi bi-eye-slash"></i>'
                : '<i class="bi bi-eye"></i>';
        });
    });

    // ===== Password strength meter =====
    const pwInput = document.getElementById('password');
    const pwMeter = document.getElementById('passwordStrength');

    if (pwInput && pwMeter) {
        pwInput.addEventListener('input', function () {
            const val = pwInput.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            pwMeter.className = 'password-strength ' +
                (score <= 1 ? 'weak' : score <= 3 ? 'fair' : score <= 4 ? 'good' : 'strong');
            const hints = pwMeter.querySelector('.password-hint');
            if (hints) {
                hints.textContent =
                    score <= 1 ? 'Weak — add more characters, mix case, and special symbols.' :
                    score <= 3 ? 'Fair — getting better. Try adding numbers and symbols.' :
                    score <= 4 ? 'Good — almost there!' :
                    'Strong — great password!';
            }
        });
    }

    // ===== Navbar scroll effect =====
    const navbar = document.querySelector('.edu-navbar');
    function onScroll() {
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 10);
    }
    window.addEventListener('scroll', onScroll);
    onScroll();

    // ===== Scroll to top button =====
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    function onScrollTopBtn() {
        if (!scrollTopBtn) return;
        scrollTopBtn.classList.toggle('show', window.scrollY > 400);
    }
    window.addEventListener('scroll', onScrollTopBtn);
    onScrollTopBtn();
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: safeAnimate ? 'auto' : 'smooth' });
        });
    }

    // ===== Stat counter animation =====
    document.querySelectorAll('.stat-value[data-count]').forEach(function (el) {
        const target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (safeAnimate) {
            el.textContent = target;
            return;
        }
        const dur = 700;
        const start = performance.now();
        function tick(now) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased);
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        }
        requestAnimationFrame(tick);
    });

    // ===== Scroll reveal =====
    if (!safeAnimate) {
        const revealEls = document.querySelectorAll('.reveal');
        if (revealEls.length) {
            const io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            revealEls.forEach(function (el, i) {
                el.style.transitionDelay = (i % 4) * 0.08 + 's';
                io.observe(el);
            });
        }
    } else {
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('visible'); });
    }

    // ===== Form submit loading state =====
    if (!prefersReducedMotion) {
        document.querySelectorAll('form[data-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('[type="submit"]');
                if (!btn) return;
                const original = btn.innerHTML;
                btn.classList.add('is-loading');
                btn.disabled = true;
                btn.setAttribute('data-original', original);
                btn.innerHTML = '<span class="btn-spinner"></span>Processing…';
            });
        });
    }

    // ===== Flash toast auto-dismiss + manual close =====
    document.querySelectorAll('.flash-toast').forEach(function (toast) {
        const close = toast.querySelector('.flash-toast-close');
        function dismiss() {
            toast.style.transition = 'opacity .3s ease, transform .3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-12px)';
            setTimeout(function () { toast.remove(); }, 300);
        }
        if (close) close.addEventListener('click', dismiss);
        // Give the user enough time to read the full message.
        setTimeout(dismiss, 8000);
    });
});
