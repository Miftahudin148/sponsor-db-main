import './bootstrap';

(() => {
    if (!document.getElementById('icm-nprogress')) {
        const el = document.createElement('div');
        el.id = 'icm-nprogress';
        el.innerHTML = '<div class="icm-bar" role="bar"></div>';
        document.body.prepend(el);
    }
    const bar = document.querySelector('#icm-nprogress .icm-bar');
    const wrap = document.getElementById('icm-nprogress');
    let progress = 0;
    let timer;
    let busy = false;
    const set = (n) => {
        progress = Math.min(1, Math.max(0, n));
        bar.style.transform = `translate3d(${(progress - 1) * 100}%,0,0)`;
    };
    const start = () => {
        if (busy) return;
        busy = true;
        wrap.classList.add('icm-busy');
        set(0.08);
        const trickle = () => {
            if (!busy) return;
            set(progress + Math.random() * 0.02);
            if (progress < 0.94) timer = setTimeout(trickle, 200);
        };
        timer = setTimeout(trickle, 200);
    };
    const done = () => {
        if (!busy) return;
        clearTimeout(timer);
        set(1);
        setTimeout(() => {
            wrap.classList.remove('icm-busy');
            setTimeout(() => set(0), 200);
            busy = false;
            progress = 0;
        }, 200);
    };
    document.addEventListener('livewire:navigating', () => {
        clearTimeout(timer);
        timer = setTimeout(start, 150);
    });
    document.addEventListener('livewire:navigated', done);
    document.addEventListener('alpine:navigating', () => {
        clearTimeout(timer);
        timer = setTimeout(start, 150);
    });
    document.addEventListener('alpine:navigated', done);
    document.addEventListener('livewire:init', () => {
        // eslint-disable-next-line no-undef
        Livewire.hook('commit', ({ succeed, fail }) => {
            let t = setTimeout(start, 150);
            succeed(() => {
                clearTimeout(t);
                done();
            });
            fail(() => {
                clearTimeout(t);
                done();
            });
        });
    });
})();
