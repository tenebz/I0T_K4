/* AquaSense — interaksi halaman error: gelembung, ubur-ubur yang bisa disentuh, dan tombol aksi. */
(function () {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const homeUrl = document.body.dataset.home || '/';

    const bubbles = document.getElementById('bubbles');
    const swarm = document.getElementById('swarm');
    const countEl = document.getElementById('jelly-count');
    const counterBtn = countEl.closest('button');
    const toastEl = document.getElementById('toast');
    const toastText = document.getElementById('toast-text');

    let touched = 0;
    let toastTimer = null;

    const rand = (min, max) => Math.random() * (max - min) + min;

    function initBubbles() {
        if (reduceMotion) return;
        for (let i = 0; i < 24; i++) {
            const b = document.createElement('div');
            const size = Math.round(rand(8, 26));
            b.className = 'rising-bubble';
            b.style.width = size + 'px';
            b.style.height = size + 'px';
            b.style.left = rand(0, 100) + '%';
            b.style.setProperty('--rise-duration', rand(6, 13).toFixed(1) + 's');
            b.style.setProperty('--rise-delay', rand(-12, 0).toFixed(1) + 's');
            bubbles.appendChild(b);
        }
    }

    const JELLY_SVG =
        '<svg width="68" height="64" viewBox="0 0 68 64" fill="none" aria-hidden="true">' +
            '<g class="tentacles">' +
                '<path d="M22 34 C19 42, 25 50, 21 58" stroke="#F472B6" stroke-width="3.5" stroke-linecap="round"/>' +
                '<path d="M30 35 C33 44, 27 52, 31 60" stroke="#EC4899" stroke-width="3.5" stroke-linecap="round"/>' +
                '<path d="M38 35 C35 44, 41 52, 37 60" stroke="#EC4899" stroke-width="3.5" stroke-linecap="round"/>' +
                '<path d="M46 34 C49 42, 43 50, 47 58" stroke="#F472B6" stroke-width="3.5" stroke-linecap="round"/>' +
            '</g>' +
            '<path d="M12 31 C12 15, 22 7, 34 7 C46 7, 56 15, 56 31 C56 35, 47 37, 34 37 C21 37, 12 35, 12 31 Z" fill="url(#jelly-grad)" stroke="#DB2777" stroke-width="2.5"/>' +
            '<circle cx="25" cy="18" r="3.5" fill="#FDF2F8" opacity="0.85"/>' +
            '<circle cx="42" cy="21" r="2.5" fill="#FCE7F3" opacity="0.8"/>' +
            '<circle cx="34" cy="14" r="2" fill="#FFFFFF" opacity="0.9"/>' +
        '</svg>';

    function createJellyfish(top, scale, duration, delay, restLeft) {
        const lane = document.createElement('div');
        lane.className = 'jelly-lane';
        lane.style.top = top + '%';
        lane.style.setProperty('--glide-duration', duration + 's');
        lane.style.setProperty('--glide-delay', delay + 's');
        lane.style.setProperty('--rest-left', restLeft + '%');

        // Ubur-ubur hanya hiasan: tidak bisa difokus keyboard; tombol penghitung di header jadi padanannya.
        const jelly = document.createElement('button');
        jelly.type = 'button';
        jelly.tabIndex = -1;
        jelly.className = 'jelly';
        jelly.style.setProperty('--jelly-scale', scale);
        jelly.style.setProperty('--bob-duration', rand(2.2, 3.4).toFixed(2) + 's');
        jelly.innerHTML = JELLY_SVG;
        jelly.addEventListener('click', (e) => {
            touched++;
            countEl.textContent = touched;
            counterBtn.setAttribute('aria-label', 'Summon a jellyfish. Jellyfish tapped: ' + touched);
            zap(jelly, 550);
            burst(e.clientX, e.clientY);
        });

        lane.appendChild(jelly);
        swarm.appendChild(lane);
    }

    function initSwarm() {
        [
            { top: 14, scale: 0.95, dur: 26, delay: -4,  rest: 8 },
            { top: 19, scale: 0.80, dur: 29, delay: -8,  rest: 22 },
            { top: 23, scale: 1.05, dur: 24, delay: -12, rest: 84 },
            { top: 28, scale: 0.75, dur: 31, delay: -16, rest: 92 },
            { top: 16, scale: 0.85, dur: 27, delay: -19, rest: 70 },
            { top: 33, scale: 0.90, dur: 25, delay: -22, rest: 4 },
        ].forEach((j) => createJellyfish(j.top, j.scale, j.dur, j.delay, j.rest));
    }

    function zap(el, ms) {
        el.classList.add('is-zapped');
        setTimeout(() => el.classList.remove('is-zapped'), ms);
    }

    function burst(x, y) {
        if (reduceMotion) return;
        for (let i = 0; i < 8; i++) {
            const dot = document.createElement('div');
            const size = Math.round(rand(6, 16));
            const angle = (Math.PI * 2 * i) / 8;
            const dist = rand(25, 70);
            dot.className = 'burst-bubble';
            dot.style.width = size + 'px';
            dot.style.height = size + 'px';
            dot.style.left = x - size / 2 + 'px';
            dot.style.top = y - size / 2 + 'px';
            dot.style.setProperty('--tx', Math.cos(angle) * dist + 'px');
            dot.style.setProperty('--ty', Math.sin(angle) * dist - 15 + 'px');
            document.body.appendChild(dot);
            setTimeout(() => dot.remove(), 800);
        }
    }

    function showToast(message) {
        toastText.textContent = message;
        toastEl.classList.add('is-shown');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('is-shown'), 3000);
    }

    function summonJellyfish() {
        createJellyfish(Math.round(rand(10, 38)), rand(0.75, 1.15).toFixed(2), Math.round(rand(20, 30)), 0, Math.round(rand(5, 95)));
        document.querySelectorAll('.jelly').forEach((el) => zap(el, 650));
        showToast(touched > 0
            ? 'A new jellyfish joined the swarm. You have tapped ' + touched + ' so far.'
            : 'A new jellyfish joined the swarm. Tap one as it drifts by!');
    }

    function goBack() {
        const sameSite = document.referrer && new URL(document.referrer).origin === window.location.origin;
        if (sameSite && window.history.length > 1) window.history.back();
        else window.location.href = homeUrl;
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        if (action === 'reload') {
            btn.setAttribute('aria-busy', 'true');
            window.location.reload();
        } else if (action === 'back') {
            goBack();
        } else if (action === 'summon') {
            summonJellyfish();
        }
    });

    initBubbles();
    initSwarm();
})();
