(function () {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const root = document.documentElement;
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme) {
        root.setAttribute('data-theme', storedTheme);
    } else if (prefersDark) {
        root.setAttribute('data-theme', 'dark');
    }

    document.querySelectorAll('[data-toggle="theme"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    });

    const toast = document.querySelector('[data-toast]');
    if (toast) {
        setTimeout(() => {
            toast.classList.add('is-hidden');
        }, 3200);
    }

    document.querySelectorAll('[data-action="copy"]').forEach((el) => {
        el.addEventListener('click', () => {
            const target = document.querySelector(el.dataset.target);
            if (!target) {
                return;
            }
            navigator.clipboard.writeText(target.textContent.trim()).then(() => {
                el.dataset.label && (el.textContent = el.dataset.label);
            });
        });
    });
})();
