/* Animasi interaktif Perpustakaan Digital: progress navigasi, tombol memuat, pesan sukses,
   muncul saat di-scroll, dan angka berhitung naik. Seluruhnya dilewati bila "kurangi gerakan" aktif. */
(function () {
    'use strict';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { return; }

    var doc = document;

    /* ---------- 1. Indikator progress saat berpindah halaman ---------- */
    var bar = doc.createElement('div');
    bar.className = 'mo-progress';
    doc.body.appendChild(bar);
    var go = function () { bar.classList.remove('on'); void bar.offsetWidth; bar.classList.add('on'); };
    window.addEventListener('pageshow', function () {
        bar.classList.remove('on');
        doc.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); });
    });

    doc.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || a.target === '_blank' || a.hasAttribute('download')) { return; }
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) { return; }
        if (a.origin !== window.location.origin) { return; }
        if (a.pathname === window.location.pathname && a.search === window.location.search && a.hash) { return; }
        go();
    });

    /* ---------- 2. Tombol "memuat" setelah form dikirim ---------- */
    doc.addEventListener('submit', function (e) {
        if (e.defaultPrevented) { return; } // dibatalkan (misalnya konfirmasi ditolak)
        go();
        var btn = e.submitter || e.target.querySelector('button[type="submit"], input[type="submit"]');
        if (btn && btn.tagName === 'BUTTON') { setTimeout(function () { btn.classList.add('is-loading'); }, 0); }
    });

    /* ---------- 3. Pesan sukses hilang sendiri ---------- */
    doc.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            el.style.maxHeight = el.offsetHeight + 'px';
            void el.offsetHeight;
            el.classList.add('mo-out');
            setTimeout(function () { el.remove(); }, 600);
        }, 6500);
    });

    /* ---------- 4. Muncul saat di-scroll (hanya elemen yang awalnya di luar layar) ---------- */
    var SELECTORS = '.stat, .pg-sum, .card, .sec-head, .bk-card, .mb-card, .ln-card, .ml-card, .dh-book, .dh-shortcuts > a, .lib-card, .bk-empty, .lib-pager, .ms-card, .cg-card, .sr-card';
    var targets = Array.prototype.slice.call(doc.querySelectorAll('.app-main ' + SELECTORS.split(', ').join(', .app-main ')));
    var vh = window.innerHeight || doc.documentElement.clientHeight;
    var hidden = targets.filter(function (el) {
        if (el.closest('.mo') || el.closest('.dropdown, .dd-menu, [x-cloak]')) { return false; }
        var r = el.getBoundingClientRect();
        return r.height > 0 && r.top > vh * 0.9;
    });

    if ('IntersectionObserver' in window && hidden.length) {
        hidden.forEach(function (el) { el.classList.add('mo'); });
        var io = new IntersectionObserver(function (entries) {
            var showing = entries.filter(function (en) { return en.isIntersecting; })
                .sort(function (a, b) { return a.target.compareDocumentPosition(b.target) & 4 ? -1 : 1; });
            showing.forEach(function (en, i) {
                var el = en.target;
                io.unobserve(el);
                el.style.transitionDelay = (Math.min(i, 8) * 70) + 'ms';
                el.classList.add('mo-in');
                // Lepas kelas bantu agar efek hover tetap berfungsi; 'mo-done' mencegah animasi masuk CSS terulang
                setTimeout(function () { el.classList.remove('mo', 'mo-in'); el.classList.add('mo-done'); el.style.transitionDelay = ''; }, 1100 + i * 70);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
        hidden.forEach(function (el) { io.observe(el); });
    }

    /* ---------- 5. Angka berhitung naik ---------- */
    var counters = Array.prototype.slice.call(doc.querySelectorAll('.stat-value, .pg-sum b, .dh-donut b, .dh-ring b'))
        .filter(function (el) { return el.children.length === 0 && /^\s*(Rp\s*)?[\d.,]+\s*$/.test(el.textContent); });

    counters.forEach(function (el) {
        var text = el.textContent.trim();
        var prefix = /^Rp\s*/.test(text) ? 'Rp ' : '';
        var target = parseInt(text.replace(/[^\d]/g, ''), 10);
        if (!target) { return; }
        var sep = text.indexOf(',') > -1 && text.indexOf('.') === -1 ? ',' : '.';
        var fmt = function (n) { return prefix + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep); };
        el.textContent = fmt(0);

        var run = function () {
            var start = null, dur = 1100;
            var step = function (t) {
                if (start === null) { start = t; }
                var p = Math.min((t - start) / dur, 1), eased = 1 - Math.pow(1 - p, 3);
                el.textContent = fmt(Math.round(target * eased));
                if (p < 1) { requestAnimationFrame(step); } else { el.textContent = text; }
            };
            requestAnimationFrame(step);
        };

        if ('IntersectionObserver' in window) {
            var co = new IntersectionObserver(function (es) {
                if (es[0].isIntersecting) { co.disconnect(); setTimeout(run, 150); }
            }, { threshold: 0.5 });
            co.observe(el);
        } else { run(); }
    });
})();
