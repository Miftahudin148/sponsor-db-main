import './bootstrap';

(() => {
    let timer = null;
    let autoHideTimer = null;
    let busy = false;
    let progress = 0;
    let lastUserInteractionTime = 0;

    const markInteraction = () => {
        lastUserInteractionTime = Date.now();
    };
    ['pointerdown', 'click', 'keydown', 'submit', 'input'].forEach(evt => {
        window.addEventListener(evt, markInteraction, { capture: true, passive: true });
    });

    const getEl = () => {
        let el = document.getElementById('icm-nprogress');
        if (!el && document.body) {
            el = document.createElement('div');
            el.id = 'icm-nprogress';
            el.innerHTML = '<div class="icm-bar" role="bar"></div>';
            document.body.prepend(el);
        }
        return el;
    };

    const set = (n) => {
        progress = Math.min(1, Math.max(0, n));
        const el = getEl();
        if (el) {
            const bar = el.querySelector('.icm-bar');
            if (bar) {
                bar.style.transform = `translate3d(${(progress - 1) * 100}%,0,0)`;
            }
        }
    };

    const done = () => {
        clearTimeout(timer);
        clearTimeout(autoHideTimer);
        if (!busy) return;
        busy = false;
        set(1);
        const el = getEl();
        if (el) {
            setTimeout(() => {
                el.classList.remove('icm-busy');
                setTimeout(() => set(0), 150);
            }, 150);
        }
    };

    const start = () => {
        const el = getEl();
        if (!el) return;
        clearTimeout(timer);
        clearTimeout(autoHideTimer);
        busy = true;
        el.classList.add('icm-busy');
        set(0.3);

        const trickle = () => {
            if (busy && progress < 0.9) {
                set(progress + (0.9 - progress) * 0.15);
                timer = setTimeout(trickle, 150);
            }
        };
        timer = setTimeout(trickle, 150);

        autoHideTimer = setTimeout(() => {
            done();
        }, 2000);
    };

    // SPA Page Navigation (selalu pemicu navigasi fitur / sidebar)
    document.addEventListener('livewire:navigating', start);
    document.addEventListener('livewire:navigated', done);
    document.addEventListener('alpine:navigating', start);
    document.addEventListener('alpine:navigated', done);

    // Tangkap klik langsung pada link / sidebar item untuk umpan balik instan 100% konsisten
    window.addEventListener('click', (e) => {
        const target = e.target.closest('a, button, .fi-sidebar-item-btn, .fi-sidebar-item');
        if (target) {
            markInteraction();
            if (target.tagName === 'A' || target.closest('.fi-sidebar-item-btn, .fi-sidebar-item')) {
                start();
            }
        }
    }, { capture: true, passive: true });

    // Livewire 3 commit hook untuk aksi/filter dalam halaman
    document.addEventListener('livewire:init', () => {
        if (window.Livewire) {
            window.Livewire.hook('commit', ({ commit, succeed, fail }) => {
                const isRecentUserAction = (Date.now() - lastUserInteractionTime) < 1200;
                const calls = commit?.calls || [];
                const isExplicitBackground = calls.some(c => c.method === '__lazyLoad' || c.method === '$refresh');

                if (!isRecentUserAction || isExplicitBackground) {
                    return;
                }

                start();

                succeed(() => {
                    done();
                });

                fail(() => {
                    done();
                });
            });
        }
    });
})();
