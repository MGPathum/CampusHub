/**
 * CampusHub - Frontend JavaScript
 * Handles: mobile nav, flash close, form validation, file preview.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile Navigation Toggle ──────────────────────────────
    const toggle = document.querySelector('.nav-toggle');
    const nav    = document.querySelector('.main-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen);
        });
    }

    // ── Flash Message Close Button ────────────────────────────
    document.querySelectorAll('.flash-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const flash = btn.closest('.flash-message');
            if (flash) flash.remove();
        });
    });

    // Auto-dismiss flash messages after 5 seconds
    const flash = document.querySelector('.flash-message');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity .4s';
            flash.style.opacity = '0';
            setTimeout(function () { flash.remove(); }, 400);
        }, 5000);
    }

    // ── Client-side Form Validation ───────────────────────────
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let valid = true;

            // Clear previous errors
            form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Required fields
            form.querySelectorAll('[required]').forEach(function (field) {
                if (!field.value.trim()) {
                    markInvalid(field, 'This field is required.');
                    valid = false;
                }
            });

            // Email fields
            form.querySelectorAll('input[type="email"]').forEach(function (field) {
                if (field.value && !isValidEmail(field.value)) {
                    markInvalid(field, 'Please enter a valid email address.');
                    valid = false;
                }
            });

            // Password confirmation
            const pass    = form.querySelector('#password');
            const confirm = form.querySelector('#password_confirm');
            if (pass && confirm && pass.value && confirm.value) {
                if (pass.value !== confirm.value) {
                    markInvalid(confirm, 'Passwords do not match.');
                    valid = false;
                }
                if (pass.value.length < 8) {
                    markInvalid(pass, 'Password must be at least 8 characters.');
                    valid = false;
                }
            }

            // Min/max numeric
            form.querySelectorAll('input[type="number"]').forEach(function (field) {
                const min = field.getAttribute('min');
                const max = field.getAttribute('max');
                if (field.value !== '') {
                    if (min !== null && parseFloat(field.value) < parseFloat(min)) {
                        markInvalid(field, `Minimum value is ${min}.`);
                        valid = false;
                    }
                    if (max !== null && parseFloat(field.value) > parseFloat(max)) {
                        markInvalid(field, `Maximum value is ${max}.`);
                        valid = false;
                    }
                }
            });

            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });

    function markInvalid(field, message) {
        field.classList.add('is-invalid');
        let errorEl = field.parentElement.querySelector('.form-error');
        if (!errorEl) {
            errorEl = document.createElement('span');
            errorEl.className = 'form-error';
            field.parentElement.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // ── File Upload Preview ───────────────────────────────────
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        const previewId = input.getAttribute('data-preview');
        const preview   = document.getElementById(previewId);
        if (!preview) return;

        input.addEventListener('change', function () {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();

            if (file.type.startsWith('image/')) {
                reader.onload = function (e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-height:200px;border-radius:8px;">`;
                };
                reader.readAsDataURL(file);

            } else if (file.type.startsWith('video/')) {
                reader.onload = function (e) {
                    preview.innerHTML = `<video controls src="${e.target.result}" style="max-height:200px;border-radius:8px;"></video>`;
                };
                reader.readAsDataURL(file);

            } else if (file.type.startsWith('audio/')) {
                reader.onload = function (e) {
                    preview.innerHTML = `<audio controls src="${e.target.result}"></audio>`;
                };
                reader.readAsDataURL(file);

            } else {
                preview.innerHTML = `<p class="text-muted" style="font-size:13px;">📄 ${file.name}</p>`;
            }
        });
    });

    // ── Confirm Delete ────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || 'Are you sure you want to delete this?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ── Active Nav Link Highlight ─────────────────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-list a').forEach(function (link) {
        if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
            link.style.background = 'var(--primary-light)';
            link.style.color      = 'var(--primary)';
        }
    });

});
