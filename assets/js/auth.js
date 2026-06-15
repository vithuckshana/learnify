/**
 * Learnify — Auth page interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    initAuthImageMotion();
    initFieldInteractions();
    initPasswordStrength();
    initRoleToggle();
    initAuthFormSubmit();
});

function initAuthImageMotion() {
    const visual = document.querySelector('.auth-split-visual');
    const image = document.querySelector('.auth-split-image');
    if (!visual || !image || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let raf = null;
    visual.addEventListener('mousemove', (e) => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
            const rect = visual.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            image.style.transform = `scale(1.12) translate(${x * 14}px, ${y * 10}px)`;
            raf = null;
        });
    });

    visual.addEventListener('mouseleave', () => {
        image.style.transform = '';
    });
}

function initFieldInteractions() {
    document.querySelectorAll('.field-wrap').forEach((wrap) => {
        const input = wrap.querySelector('.form-control');
        const hint = wrap.querySelector('.field-hint');
        if (!input) return;

        const validate = wrap.dataset.validate;

        input.addEventListener('focus', () => wrap.classList.add('is-focused'));
        input.addEventListener('blur', () => {
            wrap.classList.remove('is-focused');
            if (validate) runValidation(wrap, input, hint, validate);
        });

        if (validate) {
            input.addEventListener('input', debounce(() => {
                if (input.value.length > 0) {
                    runValidation(wrap, input, hint, validate);
                } else {
                    clearValidation(wrap, hint);
                }
            }, 280));
        }
    });
}

function runValidation(wrap, input, hint, type) {
    let ok = false;
    let message = '';

    if (type === 'email') {
        ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
        message = ok ? 'Valid email' : 'Enter a valid email address';
    } else if (type === 'name') {
        ok = input.value.trim().length >= 2;
        message = ok ? 'Looks good' : 'Name must be at least 2 characters';
    }

    wrap.classList.toggle('is-valid', ok);
    wrap.classList.toggle('is-invalid', !ok);

    if (hint) {
        hint.textContent = message;
        hint.classList.toggle('is-ok', ok);
        hint.classList.toggle('is-error', !ok);
    }

    return ok;
}

function clearValidation(wrap, hint) {
    wrap.classList.remove('is-valid', 'is-invalid');
    if (hint) {
        hint.textContent = '';
        hint.classList.remove('is-ok', 'is-error');
    }
}

function initPasswordStrength() {
    const input = document.querySelector('[data-password-strength]');
    const bar = document.querySelector('.password-strength-bar span');
    const label = document.querySelector('.password-strength-label');
    if (!input || !bar || !label) return;

    input.addEventListener('input', () => {
        const val = input.value;
        if (!val) {
            bar.style.width = '0%';
            bar.className = '';
            label.textContent = '';
            return;
        }

        const score = scorePassword(val);
        const levels = [
            { pct: '25%', cls: 'strength-weak', text: 'Weak — add length & symbols' },
            { pct: '50%', cls: 'strength-fair', text: 'Fair — keep going' },
            { pct: '75%', cls: 'strength-good', text: 'Good password' },
            { pct: '100%', cls: 'strength-strong', text: 'Strong password' },
        ];
        const level = levels[Math.min(score, 3)];

        bar.style.width = level.pct;
        bar.className = level.cls;
        label.textContent = level.text;
    });
}

function scorePassword(val) {
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    if (score <= 1) return 0;
    if (score <= 2) return 1;
    if (score <= 3) return 2;
    return 3;
}

function initRoleToggle() {
    const toggle = document.querySelector('[data-role-toggle]');
    const hidden = document.querySelector('#role-input');
    const panel = document.querySelector('#tutor-fields');
    if (!toggle || !hidden) return;

    const pill = toggle.querySelector('.role-toggle-pill');
    const buttons = toggle.querySelectorAll('[data-role]');

    const setRole = (role) => {
        hidden.value = role;
        toggle.dataset.active = role;
        buttons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.role === role);
            btn.setAttribute('aria-pressed', btn.dataset.role === role ? 'true' : 'false');
        });

        if (panel) {
            panel.classList.toggle('is-open', role === 'tutor');
            panel.querySelectorAll('.tutor-required').forEach((el) => {
                el.required = role === 'tutor';
            });
        }
    };

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => setRole(btn.dataset.role));
    });

    const initial = hidden.value || toggle.dataset.active || 'student';
    setRole(initial);
}

function initAuthFormSubmit() {
    document.querySelectorAll('form[data-auth-form]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            let valid = true;

            form.querySelectorAll('.field-wrap[data-validate]').forEach((wrap) => {
                const input = wrap.querySelector('.form-control');
                const hint = wrap.querySelector('.field-hint');
                if (input && !runValidation(wrap, input, hint, wrap.dataset.validate)) {
                    valid = false;
                }
            });

            const pw = form.querySelector('[data-password-strength]');
            if (pw && pw.required && pw.value.length < 8) {
                valid = false;
                const wrap = pw.closest('.field-wrap');
                const hint = wrap?.querySelector('.field-hint');
                if (wrap) wrap.classList.add('is-invalid');
                if (hint) {
                    hint.textContent = 'Password must be at least 8 characters';
                    hint.classList.add('is-error');
                }
            }

            const roleInput = form.querySelector('#role-input');
            if (roleInput && roleInput.value === 'tutor') {
                const subject = form.querySelector('#subject');
                const quals = form.querySelector('#qualifications');
                const cats = form.querySelectorAll('#tutor-categories input[type=checkbox]:checked');
                const hint = document.getElementById('category-hint');

                if (!subject?.value.trim() || !quals?.value.trim()) {
                    valid = false;
                }
                if (cats.length === 0) {
                    valid = false;
                    if (hint) {
                        hint.textContent = 'Select at least one category';
                        hint.classList.add('is-error');
                    }
                } else if (hint) {
                    hint.textContent = '';
                    hint.classList.remove('is-error');
                }

                if (!valid) {
                    e.preventDefault();
                    const panel = document.getElementById('tutor-fields');
                    if (panel) panel.classList.add('is-open');
                    alert('Please complete all required tutor fields (subject, qualifications, categories).');
                    return;
                }
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            const btn = form.querySelector('.btn-submit');
            if (btn && !btn.classList.contains('is-loading')) {
                btn.classList.add('is-loading');
                btn.disabled = true;
            }
        });
    });
}

function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}
