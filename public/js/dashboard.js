/* Dashboard Monitoring Kualitas Air — logika halaman (Alpine.js). */
(function () {
    'use strict';

    // Tampilan grafik per sensor. Ambang batas datang dari config/kualitas_air.php.
    const TAMPILAN = {
        ph: { lower: 'pH', dom: [4, 10], ticks: [4, 6, 8, 10] },
        tds: { lower: 'TDS', dom: [0, 700], ticks: [0, 200, 400, 600] },
        kekeruhan: { lower: 'turbidity', dom: [0, 10], ticks: [0, 2, 4, 6, 8, 10] },
        suhu: { lower: 'temperature', dom: [20, 35], ticks: [20, 25, 30, 35] },
    };
    const LV = {
        ok: { label: 'Normal', cls: 'ok' },
        warn: { label: 'Near limit', cls: 'warn' },
        bad: { label: 'Out of range', cls: 'bad' },
    };
    const GRAFIK_MAKS = 10; // jumlah percobaan terakhir yang digambar di grafik
    const JEDA_MIN = 600;   // spinner minimal terlihat sebentar walau server cepat

    // Layar pembuka & latar fluida (lihat public/js/fluid.js).
    const KUNCI_INTRO = 'kualitas-air:intro-dilihat';
    const TINTA_INTRO = 1;       // tinta penuh di layar pembuka
    const TINTA_DASHBOARD = 0.7;  // air terang tetap terlihat; kartu solid menjaga angka & grafik terbaca
    const JEDA_MASUK = 650;      // ms: konten pembuka memudar & ledakan air terlihat sebelum dashboard muncul
    const JEDA_INTRO_HILANG = 1100;
    const JEDA_KELUAR = 360;     // ms: dashboard memudar sebelum layar pembuka diputar ulang
    const DURASI_HITUNG = 900;   // ms: animasi hitung angka KPI

    const fluid = (method, ...args) => {
        if (window.fluid && typeof window.fluid[method] === 'function') window.fluid[method](...args);
    };
    // Satu IntersectionObserver bersama: jalankan callback sekali saat elemen terlihat.
    // Tanpa dukungan IO (browser lama), callback langsung dijalankan.
    const AMBANG_TERLIHAT = 0.25;
    const antreTerlihat = new WeakMap();
    let pengamat = null;
    const saatTerlihat = (el, callback) => {
        if (!('IntersectionObserver' in window)) {
            callback();
            return;
        }
        if (!pengamat) {
            pengamat = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const jalankan = antreTerlihat.get(entry.target);
                    antreTerlihat.delete(entry.target);
                    pengamat.unobserve(entry.target);
                    if (jalankan) jalankan();
                });
            }, { threshold: AMBANG_TERLIHAT, rootMargin: '0px 0px -8% 0px' });
        }
        antreTerlihat.set(el, callback);
        pengamat.unobserve(el);
        pengamat.observe(el);
    };

    // Gambar progresif grafik: --rx (persen lebar plot) bergerak dengan easeInOutSine
    // dari sedikit sebelum titik pertama ke sedikit setelah titik terakhir. Kecepatan
    // tengahnya konstan, jadi tiap titik muncul tepat saat garis mencapainya.
    const JARAK_AWAL = 3;            // % sebelum titik pertama, supaya titik itu ikut "muncul"
    const JARAK_AKHIR = 4;           // % setelah titik terakhir, menutup ujung garis
    const DURASI_GAMBAR_DASAR = 1500; // ms
    const DURASI_PER_TITIK = 130;    // ms tambahan per titik percobaan
    const DURASI_GAMBAR_MAKS = 3000; // ms
    const rafGambar = new WeakMap();
    const easeInOutSine = (p) => -(Math.cos(Math.PI * p) - 1) / 2;

    function hentikanGambar(el) {
        const id = rafGambar.get(el);
        if (id) cancelAnimationFrame(id);
        rafGambar.delete(el);
    }

    function gambarProgresif(el) {
        hentikanGambar(el);
        const x0 = parseFloat(el.dataset.x0 || '0');
        const x1 = parseFloat(el.dataset.x1 || '100');
        const n = parseInt(el.dataset.n || '6', 10);
        const dari = x0 - JARAK_AWAL;
        const ke = x1 + JARAK_AKHIR;
        const durasi = Math.min(DURASI_GAMBAR_MAKS, DURASI_GAMBAR_DASAR + n * DURASI_PER_TITIK);
        const t0 = performance.now();
        const tick = (now) => {
            const p = Math.min(1, (now - t0) / durasi);
            el.style.setProperty('--rx', (dari + (ke - dari) * easeInOutSine(p)).toFixed(2));
            if (p < 1) {
                rafGambar.set(el, requestAnimationFrame(tick));
                return;
            }
            rafGambar.delete(el);
            el.classList.remove('is-armed');
            el.style.removeProperty('--rx');
        };
        rafGambar.set(el, requestAnimationFrame(tick));
    }

    // Jalankan fn setelah keadaan awal (opacity 0) sempat dilukis: dua frame rAF,
    // dengan cadangan timer bila rAF tertahan (tab/panel di latar belakang).
    const setelahDilukis = (fn) => {
        let sudah = false;
        const sekali = () => { if (!sudah) { sudah = true; fn(); } };
        requestAnimationFrame(() => requestAnimationFrame(sekali));
        setTimeout(sekali, 80);
    };

    const introSudahDilihat = () => {
        try { return window.sessionStorage.getItem(KUNCI_INTRO) === '1'; } catch (e) { return false; }
    };

    const fmt = (v, dec) => Number(v).toFixed(dec);
    const pad2 = (n) => (n < 10 ? '0' + n : String(n));
    const cap = (t) => t.charAt(0).toUpperCase() + t.slice(1);
    const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
    const jeda = (ms) => new Promise((r) => setTimeout(r, ms));
    const joinId = (a) => {
        if (a.length <= 1) return a.join('');
        if (a.length === 2) return a[0] + ' and ' + a[1];
        return a.slice(0, -1).join(', ') + ', and ' + a[a.length - 1];
    };
    const reduceMotion = () => {
        try { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) { return false; }
    };

    // Pengaturan tampilan (menu Settings): tema & ketebalan kartu/sidebar, disimpan di
    // browser. Diterapkan langsung saat skrip dimuat di <head>, sebelum halaman dilukis,
    // supaya tidak ada kedipan tema yang salah.
    const KUNCI_PENGATURAN = 'aquasense:tampilan';
    const PENGATURAN_DEFAULT = { theme: 'light', card: 62, sidebar: 60 };
    const TEMA = ['light', 'dark', 'system'];
    const OPASITAS_MIN = 40;
    const OPASITAS_MAX = 100;
    const mqGelap = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const mqLebar = window.matchMedia ? window.matchMedia('(min-width: 1024px)') : null;
    const layarLebar = () => (mqLebar ? mqLebar.matches : true);
    const opasitasValid = (v, cadangan) => {
        const n = Math.round(Number(v));
        return Number.isFinite(n) ? Math.min(OPASITAS_MAX, Math.max(OPASITAS_MIN, n)) : cadangan;
    };
    const rapikanPengaturan = (p) => ({
        theme: TEMA.includes(p && p.theme) ? p.theme : PENGATURAN_DEFAULT.theme,
        card: opasitasValid(p && p.card, PENGATURAN_DEFAULT.card),
        sidebar: opasitasValid(p && p.sidebar, PENGATURAN_DEFAULT.sidebar),
    });
    const bacaPengaturan = () => {
        try { return rapikanPengaturan(JSON.parse(window.localStorage.getItem(KUNCI_PENGATURAN) || '{}')); } catch (e) { return { ...PENGATURAN_DEFAULT }; }
    };
    const simpanPengaturan = (p) => {
        try { window.localStorage.setItem(KUNCI_PENGATURAN, JSON.stringify(p)); } catch (e) { /* mode privat */ }
    };
    const terapkanPengaturan = (p) => {
        const root = document.documentElement;
        const gelap = p.theme === 'dark' || (p.theme === 'system' && !!mqGelap && mqGelap.matches);
        root.classList.toggle('theme-dark', gelap);
        root.style.setProperty('--card-opacity', p.card + '%');
        root.style.setProperty('--sidebar-opacity', p.sidebar + '%');
    };
    terapkanPengaturan(bacaPengaturan());

    function buatSensor(cfg) {
        return Object.keys(cfg).map((key) => {
            const c = cfg[key];
            const t = TAMPILAN[key] || { lower: c.nama.toLowerCase(), dom: [0, 100], ticks: [0, 50, 100] };
            return {
                key, nama: c.nama, satuan: c.satuan, dec: c.desimal,
                min: c.min, max: c.max, strict: !!c.ketat,
                warnLo: c.waspada_bawah, warnHi: c.waspada_atas, range: c.rentang,
                lower: t.lower, dom: t.dom, ticks: t.ticks,
            };
        });
    }

    const withUnit = (s, v) => fmt(v, s.dec) + (s.satuan ? ' ' + s.satuan : '');

    function level(s, v) {
        if (s.min !== null && v < s.min) return 'bad';
        if (s.max !== null && (s.strict ? v >= s.max : v > s.max)) return 'bad';
        if ((s.warnLo !== null && v < s.warnLo) || (s.warnHi !== null && v >= s.warnHi)) return 'warn';
        return 'ok';
    }

    function badText(s, v) {
        const val = fmt(v, s.dec);
        if (s.key === 'ph') return v < s.min ? 'Acidic pH (' + val + '), below the safe limit.' : 'Alkaline pH (' + val + '), above the safe limit.';
        if (s.key === 'tds') return 'High TDS (' + val + ' ppm), exceeds the safe limit.';
        if (s.key === 'kekeruhan') return 'High turbidity (' + val + ' NTU), filtration needed.';
        if (s.key === 'suhu') return (v < s.min ? 'Low' : 'High') + ' temperature (' + val + ' °C), outside the normal range.';
        return s.nama + ' (' + withUnit(s, v) + ') is outside the safe limit.';
    }

    function arrowFor(s, v, lv) {
        if (lv === 'bad') return (s.min !== null && v < s.min) ? ' ↓' : ' ↑';
        if (lv === 'warn') return ' ~';
        return '';
    }

    function evalTrial(SENSORS, t) {
        const bad = [];
        const warn = [];
        SENSORS.forEach((s) => {
            const lv = level(s, t[s.key]);
            if (lv === 'bad') bad.push(s); else if (lv === 'warn') warn.push(s);
        });
        let text;
        if (bad.length) text = bad.map((s) => badText(s, t[s.key])).join(' ');
        else if (warn.length) text = 'Parameters normal; ' + joinId(warn.map((s) => s.lower + ' near its limit (' + withUnit(s, t[s.key]) + ')')) + ', keep monitoring.';
        else text = 'All parameters normal, water is safe to drink.';
        return { bad, warn, baik: bad.length === 0, text };
    }

    // Cek apakah rata-rata sensor trial semuanya dalam batas
    function trialAvgBaik(SENSORS, rataRata) {
        if (!rataRata) return true;
        return SENSORS.every((s) => level(s, rataRata[s.key]) !== 'bad');
    }

    async function kirim(url, body, method) {
        let res;
        try {
            const opts = {
                method: method || 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            };
            if (method !== 'GET') opts.body = JSON.stringify(body || {});
            res = await fetch(url, opts);
        } catch (e) {
            const err = new Error('The server is not responding. Check your connection, then try again.');
            err.data = null;
            throw err;
        }
        let json = null;
        try { json = await res.json(); } catch (e) { /* bukan JSON */ }
        if (!res.ok) {
            const err = new Error((json && json.message) || 'The server is not responding. Check your connection, then try again.');
            err.data = json;
            throw err;
        }
        return json;
    }

    function buatDashboard(awal) {
        const SENSORS = buatSensor(awal.sensor);
        const lewatiIntro = introSudahDilihat();
        const nilai = (t) => evalTrial(SENSORS, t);
        // Disimpan di luar state reaktif: elemen DOM & timer tidak boleh di-proxy Alpine.
        let toastTimer = null;
        let lastFocus = null;
        const uiTimers = [];

        const NAV = [
            { id: 'ringkasan', label: 'Dashboard', icon: 'dash', view: 'dashboard', target: null },
            { id: 'riwayat', label: 'History', icon: 'chart', view: 'riwayat', target: null },
            { id: 'evaluasi', label: 'Evaluation', icon: 'table', view: 'evaluasi', target: null },
        ];

        return {
            trials: awal.percobaan || [],
            device: awal.perangkat,
            urls: awal.url,
            activeTrial: awal.active_trial || null,
            completedTrials: awal.completed_trials || [],
            sensorList: SENSORS.map((s) => ({ key: s.key, nama: s.nama })),
            sync: 'idle',
            toast: null,
            mode: 'single',
            sensorKey: SENSORS[0].key,
            hover: null,
            detail: null,
            trialDetail: null,
            sidebarOpen: layarLebar(),   // laptop: kolom terbuka; HP/tablet: laci tertutup
            settingsOpen: false,
            prefs: bacaPengaturan(),
            themeOptions: [
                { id: 'light', label: 'Light' },
                { id: 'dark', label: 'Dark' },
                { id: 'system', label: 'System' },
            ],
            nav: 'ringkasan',
            notifOpen: false,
            notifRead: false,
            query: '',
            view: 'dashboard',
            leaving: false,
            switched: false,
            filter: 'semua',
            power: awal.perangkat.menyala ? 'on' : 'off',
            syncDropdownOpen: false,
            historyLoading: false,
            newTrialStarted: false,

            // Trial input modal
            showInputDetected: !awal.active_trial,
            trialNameInput: '',
            trialNameError: '',
            trialStarting: false,
            trialPromptManual: false, // true = dibuka pengguna lewat "Start a trial", bukan deteksi sensor
            trialToDelete: null,      // trial selesai yang menunggu konfirmasi hapus
            deletingTrial: false,

            // Layar pembuka & motion
            entered: lewatiIntro,
            introGone: lewatiIntro,
            introLeaving: false,
            introReady: false,
            dashLeaving: false,
            chartRev: 0,

            init() {
                fluid('setInk', lewatiIntro ? TINTA_DASHBOARD : TINTA_INTRO);
                // Dua frame: pastikan keadaan awal (opacity 0) sudah dilukis sebelum reveal.
                setelahDilukis(() => { this.introReady = true; });

                // Responsif: sidebar jadi kolom di laptop, laci di HP/tablet.
                const dengar = (mq, fn) => {
                    if (!mq) return;
                    if (mq.addEventListener) mq.addEventListener('change', fn);
                    else if (mq.addListener) mq.addListener(fn);
                };
                dengar(mqLebar, (e) => { this.sidebarOpen = e.matches; });
                dengar(mqGelap, () => { if (this.prefs.theme === 'system') terapkanPengaturan(this.prefs); });

                // Tinggi topbar berubah saat isinya membungkus di layar sempit; sidebar memakainya.
                const topbar = this.$root.querySelector('.topbar');
                if (topbar && 'ResizeObserver' in window) {
                    new ResizeObserver(() => {
                        document.documentElement.style.setProperty('--topbar-h', Math.round(topbar.getBoundingClientRect().height) + 'px');
                    }).observe(topbar);
                }
            },

            /* ---------- Settings tampilan ---------- */
            isWide() { return layarLebar(); },
            setPref(key, value) {
                this.prefs = rapikanPengaturan({ ...this.prefs, [key]: value });
                terapkanPengaturan(this.prefs);
                simpanPengaturan(this.prefs);
            },
            resetPrefs() {
                this.prefs = { ...PENGATURAN_DEFAULT };
                terapkanPengaturan(this.prefs);
                simpanPengaturan(this.prefs);
            },
            toggleSettings() {
                if (this.settingsOpen) { this.closeSettings(false); return; }
                this.notifOpen = false;
                this.syncDropdownOpen = false;
                // Di HP laci ditutup dulu supaya perubahan kartu langsung terlihat.
                if (!layarLebar()) this.sidebarOpen = false;
                this.settingsOpen = true;
                this.$nextTick(() => {
                    const first = document.querySelector('#settings-panel .seg[aria-pressed="true"]');
                    if (first) first.focus({ preventScroll: true });
                });
            },
            closeSettings(returnFocus) {
                if (!this.settingsOpen) return;
                this.settingsOpen = false;
                if (returnFocus && this.$refs.settingsBtn) this.$refs.settingsBtn.focus({ preventScroll: true });
            },

            destroy() {
                clearTimeout(toastTimer);
                uiTimers.forEach(clearTimeout);
            },

            /* ---------- Layar pembuka ---------- */
            splitWords(el, baseDelay, stagger) {
                const words = el.textContent.trim().split(/\s+/);
                el.textContent = '';
                words.forEach((word, i) => {
                    const span = document.createElement('span');
                    span.className = 'w';
                    span.style.setProperty('--wd', (baseDelay + i * stagger) + 'ms');
                    span.style.setProperty('--i', String(i));
                    span.textContent = word;
                    el.appendChild(span);
                    if (i < words.length - 1) el.appendChild(document.createTextNode(' '));
                });
            },
            enterDashboard(view = 'dashboard', navId = 'ringkasan') {
                if (this.entered || this.introLeaving) return;
                const rm = reduceMotion();
                this.introLeaving = true;
                try { window.sessionStorage.setItem(KUNCI_INTRO, '1'); } catch (e) { /* mode privat */ }
                // Ledakan air seperti saat halaman dibuka, memancar dari tombol yang diklik
                // (atau dari tombol utama bila masuk lewat keyboard).
                let asal = titikTekanTerakhir();
                if (!asal && this.$refs.introEnter) {
                    const r = this.$refs.introEnter.getBoundingClientRect();
                    asal = { x: r.left + r.width / 2, y: r.top + r.height / 2 };
                }
                if (!rm) fluid('explode', asal ? asal.x : undefined, asal ? asal.y : undefined);
                fluid('setInk', TINTA_DASHBOARD);
                if (view && view !== this.view) {
                    this.view = view;
                    this.nav = navId;
                }
                uiTimers.push(setTimeout(() => {
                    this.entered = true;
                    this.chartRev++;
                    this.$nextTick(() => {
                        const h = document.getElementById('judul-view');
                        if (h) h.focus({ preventScroll: true });
                    });
                }, rm ? 0 : JEDA_MASUK));
                uiTimers.push(setTimeout(() => { this.introGone = true; }, rm ? 0 : JEDA_INTRO_HILANG));
            },

            // Logo AquaSense di header: kembali ke layar pembuka "Go to Dashboard".
            backToIntro() {
                if (!this.entered || this.dashLeaving) return;
                const rm = reduceMotion();
                this.notifOpen = false;
                this.syncDropdownOpen = false;
                this.hover = null;
                this.detail = null;
                this.trialDetail = null;
                this.dashLeaving = true;
                fluid('setInk', TINTA_INTRO);
                fluid('burst', 8, 1.4);
                uiTimers.push(setTimeout(() => {
                    this.entered = false;
                    this.dashLeaving = false;
                    this.introLeaving = false;
                    this.introReady = false;
                    this.introGone = false;
                    window.scrollTo(0, 0);
                    // Kata-kata judul terpasang (opacity 0) dulu, baru reveal diputar ulang.
                    this.$nextTick(() => setelahDilukis(() => {
                        this.introReady = true;
                        const cta = this.$refs.introEnter;
                        if (cta) cta.focus({ preventScroll: true });
                    }));
                }, rm ? 0 : JEDA_KELUAR));
            },

            /* ---------- Motion grafik ---------- */
            // Menyiapkan animasi "menggambar" pada grafik, sparkline, dan bar. Dipicu
            // chartRev (masuk dashboard / sinkron berhasil), lalu baru diputar saat
            // elemen masuk viewport. Garis, area, titik, label nilai, dan label sumbu-X
            // semuanya mengikuti satu posisi --rx yang bergerak dari titik percobaan
            // paling awal (data-x0) ke paling akhir (data-x1).
            replayDraw(el, rev) {
                if (!this.entered || rev < 0) return;
                hentikanGambar(el);
                if (reduceMotion()) {
                    el.classList.remove('is-armed');
                    el.style.removeProperty('--rx');
                    return;
                }
                const x0 = parseFloat(el.dataset.x0 || '0');
                el.style.setProperty('--rx', (x0 - JARAK_AWAL).toFixed(2));
                el.classList.add('is-armed');
                saatTerlihat(el, () => gambarProgresif(el));
            },

            /* ---------- Data dasar ---------- */
            get hasData() { return this.trials.length > 0; },
            get cur() { return this.trials[this.trials.length - 1] || null; },
            get prev() { return this.trials.length > 1 ? this.trials[this.trials.length - 2] : null; },
            get curEval() { return this.cur ? nilai(this.cur) : null; },
            get chartData() { return this.trials.slice(-GRAFIK_MAKS); },
            get online() { return !!this.device.online; },
            get lastSync() { return this.device.terakhir_sinkron || '—'; },
            get curNoPad() { return this.cur ? pad2(this.cur.no) : ''; },
            get sampleLabel() {
                // Sampel = nama trial yang sedang berjalan (bukan nama bawaan perangkat).
                if (!this.activeTrial || !this.activeTrial.name) return 'Trial';
                const n = this.activeTrial.name.trim();
                // "Pristine" → "Pristine Water"; nama yang sudah berarti air
                // (Aquades, Aqua…, Air…, …Water) dibiarkan apa adanya.
                return /\b(water|aqua\w*|air)\b/i.test(n) ? n : n + ' Water';
            },

            /* ---------- Perangkat & daya ---------- */
            get powerBusy() { return this.power === 'turning-on' || this.power === 'turning-off'; },
            get knobOn() { return this.power === 'on' || this.power === 'turning-on'; },
            get powerChecked() { return (this.power === 'on' || this.power === 'turning-off') ? 'true' : 'false'; },
            get powerLabel() {
                if (!this.online) return 'Unreachable';
                return { on: 'On', off: 'Off', 'turning-on': 'Turning on…', 'turning-off': 'Turning off…' }[this.power];
            },
            get powerClass() { return !this.online ? 't-dim' : (this.power === 'on' ? 't-ok' : 't-plain'); },
            // Panel Device memakai keadaan yang sama dengan chip di topbar (online / off / offline),
            // supaya keduanya tidak pernah bertentangan. Trial aktif tampil terpisah di baris "Sample".
            get deviceStatus() {
                if (!this.online) return 'Offline';
                return this.power === 'off' ? 'Off' : 'Online';
            },
            get deviceStatusClass() {
                return this.showOnline ? 'tag-ok' : 'tag-offline';
            },
            get showOnline() { return this.online && this.power !== 'off'; },
            get showPowerOff() { return this.online && this.power === 'off'; },
            get syncDisabled() { return this.sync === 'loading' || this.power !== 'on'; },

            /* ---------- Status terkini ---------- */
            get statusBaik() { return this.curEval ? this.curEval.baik : true; },
            get statusSummary() {
                if (!this.curEval) return '';
                return this.curEval.baik
                    ? 'All parameters within safe limits'
                    : cap(joinId(this.curEval.bad.map((s) => s.lower))) + ' outside safe limits';
            },
            get okCount() { return this.curEval ? SENSORS.length - this.curEval.bad.length : 0; },

            /* ---------- Tampilan kosong ---------- */
            get sensorPlaceholders() {
                return SENSORS.map((s) => ({ key: s.key, nama: s.nama, satuan: s.satuan, rangeTxt: s.range }));
            },
            get emptyStateText() {
                if (this.activeTrial) {
                    return 'Trial “' + this.activeTrial.name + '” is running. Readings appear here once the device sends data — press Sync to fetch them.';
                }
                return 'Start a trial to begin recording. Every reading the device sends — pH, TDS, turbidity, and temperature — is evaluated automatically.';
            },
            openTrialPrompt() {
                this.trialPromptManual = true;
                this.trialNameError = '';
                this.showInputDetected = true;
                this.$nextTick(() => {
                    const input = document.getElementById('trial-name-input');
                    if (input) input.focus();
                });
            },
            dismissTrialPrompt() {
                if (this.trialStarting) return;
                this.showInputDetected = false;
                this.trialNameError = '';
            },

            /* ---------- Kartu sensor ---------- */
            get sensorCards() {
                const cur = this.cur;
                if (!cur) return [];
                const prev = this.prev;
                const data = this.chartData;
                const n = data.length;
                const xPos = (i) => (n === 1 ? 50 : 3 + i * (94 / (n - 1)));
                return SENSORS.map((s) => {
                    const v = cur[s.key];
                    const bad = level(s, v) === 'bad';
                    const sy = (val) => 4 + this.yPos(s, val) * 0.32;
                    const delta = prev ? v - prev[s.key] : 0;
                    const same = !prev || Math.abs(delta) < 1e-9;
                    return {
                        key: s.key, nama: s.nama, satuan: s.satuan, rangeTxt: s.range,
                        valTxt: fmt(v, s.dec), isBad: bad,
                        tile: bad ? 'tile-bad' : 'tile-' + s.key,
                        lineColor: bad ? 'var(--bad)' : 'var(--primary)',
                        up: !same && delta > 0,
                        deltaTxt: same ? '0' : fmt(Math.abs(delta), s.dec),
                        deltaWord: !prev ? 'first trial'
                            : (same ? 'same as T' + pad2(prev.no) : (delta > 0 ? 'up from T' : 'down from T') + pad2(prev.no)),
                        spark: data.map((t, i) => xPos(i).toFixed(2) + ',' + sy(t[s.key]).toFixed(2)).join(' '),
                        dotLeft: xPos(n - 1).toFixed(2),
                        sparkX0: xPos(0).toFixed(2),
                        sparkN: n,
                        dotTop: (sy(v) / 40 * 100).toFixed(2),
                        sparkAria: s.nama + ' trend per trial: ' + data.map((t) => fmt(t[s.key], s.dec)).join('; '),
                    };
                });
            },

            /* ---------- Grafik ---------- */
            yPos(s, v) {
                return clamp((1 - (v - s.dom[0]) / (s.dom[1] - s.dom[0])) * 100, 0, 100);
            },
            get single() { return this.mode === 'single'; },
            get charts() {
                if (!this.hasData) return [];
                const sel = SENSORS.find((s) => s.key === this.sensorKey) || SENSORS[0];
                const list = this.single ? [sel] : SENSORS;
                return list.map((s) => this.buildChart(s, this.single));
            },
            buildChart(s, big) {
                const data = this.chartData;
                const n = data.length;
                const xPos = (i) => (n === 1 ? 50 : 7 + i * (86 / (n - 1)));
                const hi = s.max !== null ? s.max : s.dom[1];
                const lo = s.min !== null ? s.min : s.dom[0];
                const bandTop = this.yPos(s, hi);
                const pts = data.map((t, i) => xPos(i).toFixed(2) + ',' + this.yPos(s, t[s.key]).toFixed(2));
                const x0 = n === 1 ? '50' : '7.00';
                const x1 = n === 1 ? '50' : '93.00';

                const points = data.map((t, i) => {
                    const v = t[s.key];
                    const lv = level(s, v);
                    return {
                        id: s.key + ':' + i, i, left: xPos(i).toFixed(2), top: this.yPos(s, v).toFixed(2), lv,
                        // Di grafik sempit, label nilai berselang disembunyikan (dihitung dari titik terakhir);
                        // nilai di luar batas selalu tetap terlihat.
                        minor: n > 6 && lv !== 'bad' && (n - 1 - i) % 2 === 1,
                        label: fmt(v, s.dec) + (lv === 'bad' ? arrowFor(s, v, lv) : ''),
                        aria: 'Trial ' + t.no + ', ' + s.nama + ' ' + withUnit(s, v) + ', ' + LV[lv].label,
                    };
                });

                let tip = null;
                if (this.hover && this.hover.indexOf(s.key + ':') === 0) {
                    const hiIdx = parseInt(this.hover.split(':')[1], 10);
                    const t = data[hiIdx];
                    if (t) {
                        const lv = level(s, t[s.key]);
                        tip = {
                            left: xPos(hiIdx).toFixed(2), top: this.yPos(s, t[s.key]).toFixed(2),
                            shift: (n > 1 && hiIdx === n - 1) ? '-86%' : ((n > 1 && hiIdx === 0) ? '-14%' : '-50%'),
                            title: 'Trial ' + pad2(t.no), date: t.tanggal + ', ' + t.jam,
                            sensor: s.nama, val: withUnit(s, t[s.key]), status: LV[lv].label, cls: 'lv-' + lv,
                        };
                    }
                }

                const unit = s.satuan ? ' (' + s.satuan + ')' : '';
                return {
                    id: s.key + (big ? '-b' : '-s'),
                    key: s.key,
                    gradId: 'area-' + s.key + (big ? '-b' : '-s'),
                    title: (big ? s.nama + ' per trial' : s.nama) + unit,
                    rangeTxt: s.range,
                    h: big ? 300 : 190,
                    ticks: s.ticks.map((v) => ({ v, top: this.yPos(s, v).toFixed(2), label: fmt(v, 0) })),
                    band: { top: bandTop.toFixed(2), height: (this.yPos(s, lo) - bandTop).toFixed(2) },
                    line: pts.join(' '),
                    area: pts.join(' ') + ' ' + x1 + ',100 ' + x0 + ',100',
                    points, tip,
                    // Posisi titik percobaan pertama & terakhir untuk animasi gambar.
                    x0: xPos(0).toFixed(2), x1: xPos(n - 1).toFixed(2),
                    xlabels: data.map((t, i) => ({ no: t.no, left: xPos(i).toFixed(2), label: big ? 'Trial ' + t.no : 'T' + pad2(t.no), short: 'T' + pad2(t.no) })),
                };
            },

            /* ---------- Tabel riwayat ---------- */
            get allRows() {
                return this.trials.map((t) => {
                    const e = nilai(t);
                    const cell = (s) => {
                        const v = t[s.key];
                        const lv = level(s, v);
                        return { txt: fmt(v, s.dec) + arrowFor(s, v, lv), cls: lv };
                    };
                    const cells = {};
                    SENSORS.forEach((s) => { cells[s.key] = cell(s); });
                    return {
                        no: t.no, noPad: pad2(t.no), tanggal: t.tanggal, jam: t.jam + ' WIB',
                        cells, baik: e.baik, evaluasi: e.text,
                        hay: ('trial t' + pad2(t.no) + ' ' + t.no + ' ' + pad2(t.no) + ' ' + t.tanggal + ' ' + t.jam + ' ' + e.text + ' ' + (e.baik ? 'good' : 'poor')).toLowerCase(),
                    };
                });
            },
            get q() { return (this.query || '').trim().toLowerCase(); },
            get rows() {
                const q = this.q;
                const f = this.filter;
                return this.allRows.filter((r) => (f === 'baik' ? r.baik : (f === 'buruk' ? !r.baik : true)) && (!q || r.hay.indexOf(q) >= 0));
            },
            get total() { return this.trials.length; },
            get baikCount() { return this.allRows.filter((r) => r.baik).length; },
            get burukCount() { return this.total - this.baikCount; },
            get pct() { return this.total ? Math.round(this.baikCount / this.total * 100) : 0; },
            get filters() {
                return [
                    { id: 'semua', label: 'All', count: this.total },
                    { id: 'baik', label: 'Good', count: this.baikCount },
                    { id: 'buruk', label: 'Poor', count: this.burukCount },
                ];
            },
            get sensorHeaders() {
                return SENSORS.map((s) => ({ key: s.key, label: s.nama + (s.satuan ? ' (' + s.satuan + ')' : '') }));
            },

            /* ---------- Trial helpers ---------- */
            trialAvgClass(key, trial) {
                if (!trial.rata_rata) return '';
                const s = SENSORS.find((x) => x.key === key);
                if (!s) return '';
                return level(s, trial.rata_rata[key]);
            },
            trialStatusBaik(trial) {
                return trialAvgBaik(SENSORS, trial.rata_rata);
            },
            // Trial selesai tanpa percobaan tidak punya rata-rata: tampilkan "—", bukan 0.
            avgTxt(trial, key, dec) {
                return trial.rata_rata ? fmt(trial.rata_rata[key], dec) : '—';
            },
            avgStatus(key, trial) {
                if (!trial.rata_rata) return 'No data';
                return this.trialAvgClass(key, trial) === 'bad' ? 'Out of range' : 'Normal';
            },
            trialStatus(trial) {
                if (!trial.rata_rata) return 'none';
                return this.trialStatusBaik(trial) ? 'good' : 'poor';
            },
            openTrialDetail(trial) {
                this.trialDetail = trial;
            },
            trialPercobaanRows(trial) {
                if (!trial || !trial.percobaan) return [];
                return trial.percobaan.map((t) => {
                    const e = nilai(t);
                    const cell = (s) => {
                        const v = t[s.key];
                        const lv = level(s, v);
                        return { txt: fmt(v, s.dec) + arrowFor(s, v, lv), cls: lv };
                    };
                    const cells = {};
                    SENSORS.forEach((s) => { cells[s.key] = cell(s); });
                    return {
                        no: t.no, noPad: pad2(t.no), tanggal: t.tanggal, jam: t.jam + ' WIB',
                        cells, baik: e.baik,
                    };
                });
            },

            /* ---------- Trial actions ---------- */
            async submitTrialName() {
                const name = (this.trialNameInput || '').trim();
                if (!name) {
                    this.trialNameError = 'Trial name cannot be empty.';
                    return;
                }
                this.trialNameError = '';
                this.trialStarting = true;
                try {
                    const json = await kirim(this.urls.trial_start, { name });
                    this.activeTrial = json.trial;
                    // Trial baru mulai dari nol: Dashboard & History kosong sampai alat
                    // mengirim pembacaan pertama dan pengguna menekan Sync.
                    this.trials = [];
                    this.hover = null;
                    this.detail = null;
                    this.terapkanPerangkat(json.perangkat);
                    this.showInputDetected = false;
                    this.trialNameInput = '';
                    this.newTrialStarted = true; // flag: trial baru, data di dashboard adalah sisa trial lama
                    this.showToast({ kind: 'success', title: 'Trial "' + name + '" started', msg: 'Press Sync to fetch the first trial reading.' }, 6000);
                } catch (e) {
                    this.trialNameError = e.message;
                } finally {
                    this.trialStarting = false;
                }
            },

            async endTrial() {
                if (!this.activeTrial) return;
                this.closeToast();
                try {
                    const json = await kirim(this.urls.trial_end);
                    // Tambahkan ke completed trials
                    this.completedTrials.unshift(json.trial);
                    this.showToast({
                        kind: 'success',
                        title: 'Trial "' + json.trial.name + '" completed',
                        msg: json.trial.jumlah_percobaan > 0
                            ? 'Averages were calculated and saved to Evaluation.'
                            : 'Saved to Evaluation. No readings were recorded, so there are no averages.',
                    }, 5000);
                    // Trial pindah ke Evaluasi: Dashboard & History dikosongkan sampai
                    // trial baru dimulai.
                    this.activeTrial = null;
                    this.trials = [];
                    this.hover = null;
                    this.detail = null;
                    this.query = '';
                    this.filter = 'semua';
                    this.newTrialStarted = false;
                    // Tampilkan popup input nama trial baru
                    // Pengguna sendiri yang baru mengakhiri trial: tawarkan "Start a new trial",
                    // bukan "IoT sensor detected water" (tidak ada deteksi apa pun di sini).
                    this.trialPromptManual = true;
                    this.trialNameError = '';
                    this.showInputDetected = true;
                } catch (e) {
                    this.showToast({ kind: 'error', retry: 'sync', title: 'Could not end the trial', msg: e.message });
                }
            },

            /* ---------- Hapus evaluasi ---------- */
            askDeleteTrial(trial) {
                this.trialToDelete = trial;
                this.$nextTick(() => {
                    const btn = document.getElementById('btn-batal-hapus');
                    if (btn) btn.focus();
                });
            },
            cancelDeleteTrial() {
                if (this.deletingTrial) return;
                this.trialToDelete = null;
            },
            async confirmDeleteTrial() {
                const trial = this.trialToDelete;
                if (!trial || this.deletingTrial) return;
                this.deletingTrial = true;
                try {
                    const json = await kirim(this.urls.trial_destroy.replace(':id', trial.id), null, 'DELETE');
                    this.completedTrials = json.trials;
                    if (this.trialDetail && this.trialDetail.id === trial.id) this.trialDetail = null;
                    this.trialToDelete = null;
                    this.showToast({ kind: 'success', title: 'Trial "' + trial.name + '" deleted', msg: 'It was removed from Evaluation together with its readings.' }, 4000);
                } catch (e) {
                    this.trialToDelete = null;
                    this.showToast({ kind: 'error', retry: 'delete', trial, title: 'Could not delete the trial', msg: e.message });
                } finally {
                    this.deletingTrial = false;
                }
            },

            async refreshHistory() {
                this.historyLoading = true;
                try {
                    const json = await kirim(this.urls.trial_history, null, 'GET');
                    this.completedTrials = json.trials;
                } catch (e) {
                    this.showToast({ kind: 'error', retry: 'sync', title: 'Could not load history', msg: e.message });
                } finally {
                    this.historyLoading = false;
                }
            },

            /* ---------- Notifikasi ---------- */
            get notifs() {
                const list = [];
                if (this.cur && this.device.terakhir_sinkron) {
                    list.push({ id: 'sync', kind: 'ok', title: 'Last sync successful', sub: 'Trial ' + pad2(this.cur.no) + ' data received · ' + this.lastSync, no: null });
                }
                this.trials.slice().reverse().forEach((t) => {
                    if (list.length >= 6) return;
                    const e = nilai(t);
                    if (e.bad.length) list.push({ id: 'p' + t.no, kind: 'bad', title: 'Trial ' + pad2(t.no) + ': poor water quality', sub: e.text, no: t.no });
                    else if (e.warn.length) list.push({ id: 'p' + t.no, kind: 'warn', title: 'Trial ' + pad2(t.no) + ': needs monitoring', sub: e.text, no: t.no });
                });
                return list;
            },
            get unread() { return this.notifRead ? 0 : this.notifs.length; },
            get bellAria() { return this.unread > 0 ? 'Notifications, ' + this.unread + ' unread' : 'Notifications'; },
            openNotif(m) {
                if (m.no) this.openDetail(m.no); else this.notifOpen = false;
            },

            /* ---------- Navigasi & tampilan ---------- */
            get navs() {
                return NAV.map((nd) => ({
                    ...nd,
                    active: this.nav === nd.id,
                    badge: nd.id === 'riwayat' && this.burukCount > 0 ? this.burukCount : null,
                }));
            },
            get viewClass() { return this.leaving ? 'view-out' : (this.switched ? 'view-in' : ''); },
            get viewTitle() {
                if (this.view === 'riwayat') return 'Trial History';
                if (this.view === 'evaluasi') return 'Evaluation';
                return 'Water Quality Monitoring Dashboard';
            },
            get viewSub() {
                if (this.view === 'riwayat') return 'Every trial with its automatic evaluation notes' + (this.activeTrial ? ' — ' + this.activeTrial.name : '') + '.';
                if (this.view === 'evaluasi') return 'History of all completed drinking water trials.';
                if (!this.activeTrial) return 'No active trial · start a trial to begin monitoring';
                if (!this.cur) return 'Sample ' + this.activeTrial.name + ' · waiting for the first reading';
                return 'Sample ' + this.activeTrial.name + ' · ' + this.total + (this.total === 1 ? ' trial' : ' trials') + ' · latest data from Trial ' + this.curNoPad;
            },
            scrollToTarget(target) {
                const behavior = reduceMotion() ? 'auto' : 'smooth';
                const el = target ? document.getElementById(target) : null;
                if (el) el.scrollIntoView({ behavior, block: 'start' });
                else window.scrollTo({ top: 0, behavior });
            },
            go(view, navId, target, keepFocus) {
                if (this.leaving) return;
                this.notifOpen = false;
                this.syncDropdownOpen = false;
                if (view === this.view) {
                    this.nav = navId;
                    this.scrollToTarget(target);
                    return;
                }
                this.leaving = true;
                this.switched = true;
                this.nav = navId;
                this.hover = null;
                uiTimers.push(setTimeout(() => {
                    this.view = view;
                    this.leaving = false;
                    this.$nextTick(() => {
                        if (!keepFocus) {
                            const h = document.getElementById('judul-view');
                            if (h) h.focus({ preventScroll: true });
                        }
                        this.scrollToTarget(target);
                    });
                }, reduceMotion() ? 0 : 120));
            },
            pickNav(n) {
                if (!layarLebar()) this.sidebarOpen = false;   // laci HP menutup setelah memilih menu
                this.go(n.view, n.id, n.target);
            },
            onQuery(v) {
                this.query = v;
                if (v.trim() && this.view !== 'riwayat') this.go('riwayat', 'riwayat', null, true);
            },
            clearAll() { this.query = ''; this.filter = 'semua'; },

            /* ---------- Toast ---------- */
            showToast(t, autoHideMs) {
                clearTimeout(toastTimer);
                this.toast = t;
                if (autoHideMs) toastTimer = setTimeout(() => { this.toast = null; }, autoHideMs);
            },
            closeToast() { clearTimeout(toastTimer); this.toast = null; },
            toastRetry() {
                const t = this.toast;
                if (t && t.retry === 'power') this.togglePower();
                else if (t && t.retry === 'delete' && t.trial) { this.closeToast(); this.askDeleteTrial(t.trial); }
                else this.doSync();
            },

            /* ---------- Aksi ke server ---------- */
            terapkanPerangkat(p) {
                this.device = p;
                if (!this.powerBusy) this.power = p.menyala ? 'on' : 'off';
            },
            async doSync() {
                if (this.sync === 'loading' || this.power !== 'on') return;
                this.sync = 'loading';
                this.closeToast();
                fluid('setTempo', 2.2);
                try {
                    const [json] = await Promise.all([kirim(this.urls.sinkron), jeda(JEDA_MIN)]);
                    const sebelum = this.trials.length;
                    this.trials = json.percobaan;
                    this.chartRev++;
                    fluid('burst', 8, 1.3);
                    this.terapkanPerangkat(json.perangkat);
                    if (json.active_trial) this.activeTrial = json.active_trial;
                    this.newTrialStarted = false; // flag trial baru dibersihkan setelah sync
                    if (this.trials.length > sebelum) this.notifRead = false;
                    const baru = this.trials.length - sebelum;
                    let pesanSinkron = 'No new readings since the last sync.';
                    if (baru > 0) pesanSinkron = baru + (baru === 1 ? ' new trial' : ' new trials') + ' received from the device.';
                    else if (this.trials.length === 0) pesanSinkron = 'No readings yet — the device has not sent data for this trial.';
                    this.showToast({
                        kind: 'success',
                        title: 'Data synced successfully',
                        msg: pesanSinkron,
                    }, 4000);
                } catch (e) {
                    if (e.data && e.data.perangkat) this.terapkanPerangkat(e.data.perangkat);
                    this.showToast({ kind: 'error', retry: 'sync', title: 'Sync failed', msg: e.message });
                } finally {
                    this.sync = 'idle';
                    fluid('setTempo', 1);
                }
            },
            async togglePower() {
                const p = this.power;
                if (!this.online || this.powerBusy) return;
                const turningOn = p === 'off';
                this.power = turningOn ? 'turning-on' : 'turning-off';
                this.closeToast();
                try {
                    const [json] = await Promise.all([kirim(this.urls.daya, { menyala: turningOn }), jeda(JEDA_MIN)]);
                    this.device = json.perangkat;
                    this.power = json.perangkat.menyala ? 'on' : 'off';
                    if (turningOn) {
                        this.showToast({ kind: 'success', title: 'Device turned on', msg: 'Sensors are reading again. Sync to fetch the latest data.' }, 4000);
                    } else {
                        this.showToast({ kind: 'info', title: 'Device turned off', msg: 'Sensors stop reading data until the device is turned on again.' });
                    }
                } catch (e) {
                    this.power = turningOn ? 'off' : 'on';
                    if (e.data && e.data.perangkat) this.device = e.data.perangkat;
                    this.showToast({ kind: 'error', retry: 'power', title: 'Command did not reach the device', msg: e.message });
                }
            },

            /* ---------- Dialog detail ---------- */
            get detailIdx() { return this.detail ? this.trials.findIndex((t) => t.no === this.detail) : -1; },
            get d() {
                const idx = this.detailIdx;
                if (idx < 0) return null;
                const t = this.trials[idx];
                const e = nilai(t);
                return {
                    no: t.no, noPad: pad2(t.no), tanggal: t.tanggal, jam: t.jam,
                    baik: e.baik, evaluasi: e.text,
                    rows: SENSORS.map((s) => {
                        const v = t[s.key];
                        const lv = level(s, v);
                        return { key: s.key, nama: s.nama, val: withUnit(s, v) + arrowFor(s, v, lv), range: s.range, status: LV[lv].label, lv };
                    }),
                    noPrev: idx <= 0,
                    noNext: idx >= this.trials.length - 1,
                };
            },
            openDetail(no) {
                lastFocus = document.activeElement;
                this.detail = no;
                this.notifOpen = false;
                this.$nextTick(() => {
                    const dlg = document.getElementById('dialog-detail');
                    if (dlg) dlg.focus();
                });
            },
            closeDetail() {
                const no = this.detail;
                this.detail = null;
                this.$nextTick(() => {
                    const target = (lastFocus && lastFocus !== document.body && document.contains(lastFocus))
                        ? lastFocus
                        : document.getElementById('btn-percobaan-' + no);
                    if (target && target.focus) target.focus();
                });
            },
            stepDetail(dir) {
                const idx = this.detailIdx + dir;
                if (idx >= 0 && idx < this.trials.length) this.detail = this.trials[idx].no;
            },
            dialogKey(e) {
                if (e.key === 'Escape') { e.stopPropagation(); this.closeDetail(); return; }
                if (e.key !== 'Tab') return;
                const dlg = document.getElementById('dialog-detail');
                if (!dlg) return;
                const f = Array.from(dlg.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')).filter((x) => !x.disabled);
                if (!f.length) return;
                const first = f[0];
                const last = f[f.length - 1];
                const active = document.activeElement;
                if (e.shiftKey && (active === first || active === dlg)) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && active === last) { e.preventDefault(); first.focus(); }
            },
        };
    }

    // x-countup="'7.2'": angka berhitung halus ke nilai baru (desimal titik).
    // null = belum tampil; hitungan berikutnya dimulai dari 0.
    function daftarCountUp(Alpine) {
        Alpine.directive('countup', (el, { expression }, { evaluateLater, effect, cleanup }) => {
            const ambilNilai = evaluateLater(expression);
            let raf = 0;
            let shown = null;

            const flash = () => {
                el.classList.remove('is-updated');
                void el.offsetWidth;
                el.classList.add('is-updated');
            };

            effect(() => ambilNilai((txt) => {
                cancelAnimationFrame(raf);
                if (txt === null || txt === undefined) {
                    el.textContent = '';
                    shown = null;
                    return;
                }
                const str = String(txt);
                const target = parseFloat(str);
                const dec = (str.split('.')[1] || '').length;
                if (!isFinite(target) || reduceMotion()) {
                    el.textContent = str;
                    shown = target;
                    return;
                }
                const from = shown === null ? 0 : shown;
                if (from === target) {
                    el.textContent = str;
                    return;
                }
                if (shown !== null) flash();
                el.textContent = fmt(from, dec);
                const t0 = performance.now();
                const tick = (now) => {
                    const p = Math.min(1, (now - t0) / DURASI_HITUNG);
                    const eased = 1 - Math.pow(1 - p, 3);
                    shown = from + (target - from) * eased;
                    el.textContent = p < 1 ? fmt(shown, dec) : str;
                    if (p < 1) raf = requestAnimationFrame(tick);
                    else shown = target;
                };
                raf = requestAnimationFrame(tick);
            }));
            cleanup(() => cancelAnimationFrame(raf));
        });
    }

    // Tombol yang "kebanjiran" saat disentuh kursor (lihat .flood di CSS) juga
    // memercikkan air ke latar fluida, tepat di tengah tombol.
    const SELEKTOR_BANJIR = '.btn-primary, .btn-outline, .btn-soft, .btn-warn, .btn-retry, .btn-close, .pill';
    function pasangPercikanTombol() {
        document.addEventListener('pointerover', (e) => {
            if (e.pointerType === 'touch' || reduceMotion()) return;
            const btn = e.target.closest(SELEKTOR_BANJIR);
            if (!btn || btn.disabled || btn.contains(e.relatedTarget)) return;
            const r = btn.getBoundingClientRect();
            fluid('splash', r.left + r.width / 2, r.top + r.height / 2);
        }, { passive: true });
    }

    // Posisi tekan terakhir: sumber ledakan air saat "Go to Dashboard" diklik.
    let titikTekan = null;
    const titikTekanTerakhir = () => (titikTekan && performance.now() - titikTekan.t < 1500 ? titikTekan : null);

    // Riak air kecil di titik tekan pada tombol (.btn / .pill).
    function pasangRiakTombol() {
        document.addEventListener('pointerdown', (e) => {
            titikTekan = { x: e.clientX, y: e.clientY, t: performance.now() };
            const btn = e.target.closest('.btn, .pill');
            if (!btn || btn.disabled || reduceMotion()) return;
            const r = btn.getBoundingClientRect();
            const size = Math.max(r.width, r.height) * 2;
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.setAttribute('aria-hidden', 'true');
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - r.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - r.top - size / 2) + 'px';
            btn.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        }, { passive: true });
    }

    document.addEventListener('alpine:init', () => {
        const el = document.getElementById('data-awal');
        const awal = JSON.parse(el.textContent);
        daftarCountUp(window.Alpine);
        window.Alpine.data('dashboard', () => buatDashboard(awal));
        pasangRiakTombol();
        pasangPercikanTombol();
    });
})();
