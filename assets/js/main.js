/**
 * Learnify — Cinematic Premium Dark Frontend
 */

const VIDEO_SRC =
    'https://res.cloudinary.com/dfonotyfb/video/upload/v1775585556/dds3_1_rqhg7x.mp4';

document.addEventListener('DOMContentLoaded', () => {
    initStaggerAnimations();
    initNavbarScroll();
    initParallax();
    initAmbientVideos();
    initHeroRotate();
    initHeroCursorGlow();
    initMobileNav();
});

function initMobileNav() {
    const toggle = document.getElementById('nav-toggle');
    const links = document.getElementById('navbar-links');
    if (!toggle || !links) return;
    toggle.addEventListener('click', () => links.classList.toggle('is-open'));
}

function initStaggerAnimations() {
    const targets = document.querySelectorAll(
        '.hero-content > *, .anim-in, .auth-form-inner, .page-header, .card'
    );

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12 }
    );

    targets.forEach((el, index) => {
        if (!el.classList.contains('anim-in')) {
            el.classList.add('anim-in');
        }
        el.style.transitionDelay = `${Math.min(index * 0.12, 0.6)}s`;
        observer.observe(el);
    });

    // Hero: immediate reveal on load
    document.querySelectorAll('.hero-content > .anim-in').forEach((el, i) => {
        setTimeout(() => el.classList.add('is-visible'), 80 + i * 150);
    });
}

function initNavbarScroll() {
    const nav = document.querySelector('.navbar');
    if (!nav || document.querySelector('.hero')) return;

    const onScroll = () => {
        nav.classList.toggle('is-scrolled', window.scrollY > 24);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

function initParallax() {
    const video =
        document.querySelector('.hero video') ||
        document.querySelector('.ambient-video video');

    if (!video) return;

    let raf = null;
    document.addEventListener('mousemove', (e) => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
            const moveX = (e.clientX / window.innerWidth - 0.5) * 8;
            const moveY = (e.clientY / window.innerHeight - 0.5) * 8;
            video.style.transform = `translate(${moveX}px, ${moveY}px) scale(1.06)`;
            raf = null;
        });
    });
}

function initHeroRotate() {
    const el = document.getElementById('hero-rotate');
    if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const phrases = [
        'Without Limits',
        'With Expert Tutors',
        'On Your Terms',
        'Every Single Day',
    ];
    let index = 0;

    const cycle = () => {
        el.classList.add('is-exiting');
        setTimeout(() => {
            index = (index + 1) % phrases.length;
            el.textContent = phrases[index];
            el.classList.remove('is-exiting');
            el.classList.add('is-entering');
            requestAnimationFrame(() => el.classList.remove('is-entering'));
        }, 420);
    };

    setInterval(cycle, 3400);
}

function initHeroCursorGlow() {
    const hero = document.querySelector('.hero');
    if (!hero || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const glow = document.createElement('div');
    glow.className = 'hero-cursor-glow';
    hero.appendChild(glow);

    let raf = null;
    hero.addEventListener('mousemove', (e) => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
            const rect = hero.getBoundingClientRect();
            glow.style.left = `${e.clientX - rect.left}px`;
            glow.style.top = `${e.clientY - rect.top}px`;
            raf = null;
        });
    });
}

function initAmbientVideos() {
    document.querySelectorAll('[data-video-bg]').forEach((wrap) => {
        if (wrap.querySelector('video')) return;

        const video = document.createElement('video');
        video.autoplay = true;
        video.muted = true;
        video.loop = true;
        video.playsInline = true;
        video.setAttribute('playsinline', '');

        const source = document.createElement('source');
        source.src = wrap.dataset.videoBg || VIDEO_SRC;
        source.type = 'video/mp4';
        video.appendChild(source);
        wrap.prepend(video);
    });
}
