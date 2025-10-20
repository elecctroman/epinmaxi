(function () {
    const root = document.documentElement;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
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
        setTimeout(() => toast.remove(), 3200);
    }

    const quickSearch = document.querySelector('[data-quick-search]');
    const searchResults = document.querySelector('[data-search-results]');
    if (quickSearch && searchResults) {
        let timer;
        quickSearch.addEventListener('input', (event) => {
            const query = event.target.value.trim();
            clearTimeout(timer);
            if (!query) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
                return;
            }
            timer = setTimeout(() => {
                fetch(`/search?q=${encodeURIComponent(query)}`)
                    .then((res) => res.json())
                    .then((data) => {
                        searchResults.innerHTML = data.data
                            .map((item) => `<a href="/urun/${item.slug}">${item.title} <small>${Number(item.sale_price ?? item.price).toFixed(2)} ${item.currency}</small></a>`)
                            .join('');
                        searchResults.style.display = data.data.length ? 'block' : 'none';
                    })
                    .catch(() => {
                        searchResults.innerHTML = '<span style="display:block;padding:0.75rem;">Arama sırasında hata oluştu.</span>';
                        searchResults.style.display = 'block';
                    });
            }, 260);
        });
        document.addEventListener('click', (e) => {
            if (!searchResults.contains(e.target) && e.target !== quickSearch) {
                searchResults.style.display = 'none';
            }
        });
    }

    const updateMiniCart = (count) => {
        const badge = document.querySelector('[data-mini-cart-count]');
        if (badge) {
            badge.textContent = count;
        }
    };

    document.querySelectorAll('[data-add-to-cart]').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const productId = btn.dataset.product;
            fetch('/sepete-ekle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `_token=${encodeURIComponent(window.csrfToken || document.querySelector('input[name="_token"]').value)}&product_id=${productId}&qty=1`
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.cart) {
                        updateMiniCart(data.cart.items.length);
                    }
                    showToast(data.message || 'Sepete eklendi');
                })
                .catch(() => showToast('Sepete eklenemedi', 'danger'));
        });
    });

    const couponForm = document.querySelector('[data-coupon-form]');
    if (couponForm) {
        couponForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(couponForm);
            fetch('/kupon/uygula', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.error) {
                        showToast(data.error, 'danger');
                    } else {
                        showToast(data.message || 'Kupon uygulandı', 'success');
                        window.location.reload();
                    }
                })
                .catch(() => showToast('Kupon uygulanamadı', 'danger'));
        });
    }

    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.copy || btn.textContent;
            navigator.clipboard.writeText(text).then(() => showToast('Panoya kopyalandı', 'success'));
        });
    });

    const tabs = document.querySelectorAll('[data-tabs]');
    tabs.forEach((tabContainer) => {
        tabContainer.querySelectorAll('button').forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.tabTarget;
                tabContainer.querySelectorAll('button').forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('[data-tab-panel]').forEach((panel) => {
                    panel.hidden = panel.id !== target;
                });
            });
        });
    });

    function showToast(message, level = 'info') {
        let toast = document.querySelector('#dynamic-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'dynamic-toast';
            toast.className = `toast alert alert-${level}`;
            document.body.appendChild(toast);
        }
        toast.className = `toast alert alert-${level}`;
        toast.textContent = message;
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3200);
    }
})();
