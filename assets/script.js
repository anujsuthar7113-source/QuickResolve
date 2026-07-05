// ============================================================
// assets/js/script.js — QuickResolve_18 Global JavaScript
// Smart Complaint Management System
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Animated Counter (Dashboard) ─────────────────────────
    // Counts numbers up from 0 to target value on page load
    function animateCounters() {
        const counters = document.querySelectorAll('[data-count]');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 1200; // milliseconds
            const stepTime = Math.max(20, Math.floor(duration / target));
            let current = 0;

            const timer = setInterval(() => {
                current += Math.ceil(target / 60);
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = current.toLocaleString();
            }, stepTime);
        });
    }

    // Run counters if elements exist on the page
    if (document.querySelector('[data-count]')) {
        // Small delay so page is fully rendered
        setTimeout(animateCounters, 200);
    }

    // ── Password Strength Validator ───────────────────────────
    // Validates password and shows a strength bar
    const passwordInput = document.getElementById('password');
    const strengthBar   = document.getElementById('strengthBar');
    const strengthText  = document.getElementById('strengthText');

    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;

            // Check each criterion and add to score
            if (val.length >= 8)          score++;  // length
            if (/[A-Z]/.test(val))        score++;  // uppercase
            if (/[a-z]/.test(val))        score++;  // lowercase
            if (/\d/.test(val))           score++;  // number
            if (/[^A-Za-z0-9]/.test(val)) score++;  // special char

            // Map score to label and color
            const levels = [
                { pct: 0,   color: '#E5E7EB', label: '' },
                { pct: 20,  color: '#EF4444', label: 'Very Weak' },
                { pct: 40,  color: '#F97316', label: 'Weak' },
                { pct: 60,  color: '#F59E0B', label: 'Fair' },
                { pct: 80,  color: '#10B981', label: 'Strong' },
                { pct: 100, color: '#059669', label: 'Very Strong' },
            ];

            const lvl = levels[score];
            strengthBar.style.width     = lvl.pct + '%';
            strengthBar.style.background = lvl.color;
            if (strengthText) strengthText.textContent = lvl.label;
        });
    }

    // ── Confirm Password Match ────────────────────────────────
    const confirmInput  = document.getElementById('confirm_password');
    const matchFeedback = document.getElementById('matchFeedback');

    if (confirmInput && passwordInput) {
        confirmInput.addEventListener('input', function () {
            if (matchFeedback) {
                if (this.value === passwordInput.value) {
                    matchFeedback.textContent = '✓ Passwords match';
                    matchFeedback.className   = 'form-text text-success';
                } else {
                    matchFeedback.textContent = '✗ Passwords do not match';
                    matchFeedback.className   = 'form-text text-danger';
                }
            }
        });
    }

    // ── Form Validation Helper ────────────────────────────────
    // Prevents form submission if required fields are empty
    const forms = document.querySelectorAll('.qr-validate');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const required = form.querySelectorAll('[required]');
            let valid = true;

            required.forEach(field => {
                field.classList.remove('is-invalid');
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                }
            });

            if (!valid) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'warning');
            }
        });
    });

    // ── Image Preview on File Select ─────────────────────────
    // Shows a preview when user picks a complaint image
    const imgInput   = document.getElementById('complaint_image');
    const imgPreview = document.getElementById('imgPreview');

    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    imgPreview.src     = e.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imgPreview.style.display = 'none';
            }
        });
    }

    // ── Star Rating Widget ────────────────────────────────────
    // Interactive star rating for feedback form
    const stars     = document.querySelectorAll('.star-rating .star');
    const ratingInput = document.getElementById('ratingInput');

    stars.forEach((star, index) => {
        star.addEventListener('click', function () {
            // Store value in hidden input
            if (ratingInput) ratingInput.value = index + 1;

            // Visually mark clicked and all previous stars active
            stars.forEach((s, i) => {
                s.classList.toggle('active', i <= index);
            });
        });

        star.addEventListener('mouseover', function () {
            stars.forEach((s, i) => {
                s.style.color = i <= index ? '#F59E0B' : '#E2E8F0';
            });
        });

        star.addEventListener('mouseleave', function () {
            const current = ratingInput ? parseInt(ratingInput.value) : 0;
            stars.forEach((s, i) => {
                s.style.color = i < current ? '#F59E0B' : '#E2E8F0';
            });
        });
    });

    // ── Inline Alert Helper ───────────────────────────────────
    // Creates a temporary Bootstrap alert at top of page
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow-sm position-fixed`;
        alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; max-width: 360px;';
        alertDiv.innerHTML = `${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);

        // Auto-remove after 4 seconds
        setTimeout(() => alertDiv.remove(), 4000);
    }

    // Expose globally so PHP-generated pages can call it
    window.showQRAlert = showAlert;

    // ── Auto-dismiss Flash Alerts ─────────────────────────────
    const flashAlerts = document.querySelectorAll('.auto-dismiss');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 4500);
    });

    // ── Tooltip Initialization ────────────────────────────────
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(el => new bootstrap.Tooltip(el));

    // ── Sidebar Active Link ───────────────────────────────────
    // Highlight the sidebar link matching current URL
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });

    // ── Confirm Delete / Destructive Actions ──────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ── Hero section counter animation ───────────────────────
    // Specifically for landing page stats section
    const statNumbers = document.querySelectorAll('.stat-number[data-count]');
    if (statNumbers.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseInt(el.getAttribute('data-count'));
                    let current = 0;
                    const step = Math.ceil(target / 50);
                    const timer = setInterval(() => {
                        current += step;
                        if (current >= target) { current = target; clearInterval(timer); }
                        el.textContent = current.toLocaleString() + (el.getAttribute('data-suffix') || '');
                    }, 30);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        statNumbers.forEach(el => observer.observe(el));
    }

    // ── Dynamic keyword hint for complaint form ───────────────
    // Shows which department will handle based on keywords typed
    const descArea  = document.getElementById('description');
    const routeHint = document.getElementById('routeHint');

    if (descArea && routeHint) {
        const deptMap = {
            electrical: ['light', 'electricity', 'bulb', 'wire', 'power', 'switch', 'fan', 'socket', 'voltage'],
            plumbing:   ['water', 'pipe', 'leak', 'tap', 'drain', 'flush', 'sewage', 'toilet', 'overflow'],
            housekeeping: ['clean', 'dirty', 'garbage', 'waste', 'sweep', 'mop', 'trash', 'hygiene', 'dust'],
            'it support':  ['internet', 'wifi', 'network', 'computer', 'laptop', 'software', 'printer', 'server'],
            maintenance:  ['repair', 'broken', 'crack', 'wall', 'ceiling', 'door', 'window', 'paint', 'civil'],
            security:    ['security', 'cctv', 'camera', 'lock', 'theft', 'noise', 'safety', 'guard', 'access'],
        };

        descArea.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            let detected = null;

            for (const [dept, keywords] of Object.entries(deptMap)) {
                if (keywords.some(kw => val.includes(kw))) {
                    detected = dept;
                    break;
                }
            }

            if (detected) {
                routeHint.innerHTML = `<i class="fas fa-magic me-2"></i>Smart Routing: Will be assigned to <strong class="text-capitalize">${detected}</strong> department`;
                routeHint.className = 'mt-2 small text-primary';
            } else {
                routeHint.innerHTML = '<i class="fas fa-user-tie me-2"></i>No keyword detected — Admin will manually assign';
                routeHint.className = 'mt-2 small text-muted';
            }
        });
    }

}); // end DOMContentLoaded
