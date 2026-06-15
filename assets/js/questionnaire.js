document.addEventListener('DOMContentLoaded', () => {
    const steps = [...document.querySelectorAll('.wizard-step')];
    const prev = document.getElementById('wizard-prev');
    const next = document.getElementById('wizard-next');
    const submit = document.getElementById('wizard-submit');
    const indicator = document.getElementById('step-indicator');
    const progress = document.getElementById('progress-fill');
    if (!steps.length || !next) return;

    let current = 0;

    const update = () => {
        steps.forEach((s, i) => s.classList.toggle('is-active', i === current));
        if (indicator) indicator.textContent = String(current + 1);
        if (progress) progress.style.width = `${((current + 1) / steps.length) * 100}%`;
        if (prev) prev.disabled = current === 0;
        if (next) next.style.display = current === steps.length - 1 ? 'none' : 'inline-flex';
        if (submit) submit.style.display = current === steps.length - 1 ? 'inline-flex' : 'none';
    };

    const validateStep = () => {
        const step = steps[current];
        if (current === 0) {
            const ta = step.querySelector('#about_me');
            return ta && ta.value.trim().length >= 10;
        }
        if (current === 2) {
            return step.querySelectorAll('input[type=checkbox]:checked').length > 0;
        }
        return true;
    };

    next.addEventListener('click', () => {
        if (!validateStep()) {
            alert('Please complete this step before continuing.');
            return;
        }
        if (current < steps.length - 1) {
            current++;
            update();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    prev?.addEventListener('click', () => {
        if (current > 0) {
            current--;
            update();
        }
    });

    update();
});
