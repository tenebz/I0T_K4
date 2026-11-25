/* Dashboard Monitoring Kualitas Air — logika halaman (Alpine.js). */
(function () {
    'use strict';

    // Tampilan grafik per sensor. Ambang batas datang dari config/kualitas_air.php.
    const TAMPILAN = {
        ph: { lower: 'pH', dom: [4, 10], ticks: [4, 6, 8, 10] },
        tds: { lower: 'TDS', dom: [0, 700], ticks: [0, 200, 400, 600] },
        kekeruhan: { lower: 'kekeruhan', dom: [0, 10], ticks: [0, 2, 4, 6, 8, 10] },
        suhu: { lower: 'suhu', dom: [20, 35], ticks: [20, 25, 30, 35] },
    };
    const LV = {
        ok: { label: 'Normal', cls: 'ok' },
        warn: { label: 'Mendekati batas', cls: 'warn' },
        bad: { label: 'Tidak normal', cls: 'bad' },
    };
    const GRAFIK_MAKS = 10; // jumlah percobaan terakhir yang digambar di grafik
    const JEDA_MIN = 600;   // spinner minimal terlihat sebentar walau server cepat

    // Layar pembuka & latar fluida (lihat public/js/fluid.js).
    const KUNCI_INTRO = 'kualitas-air:intro-dilihat';
    const TINTA_INTRO = 1;       // tinta penuh di layar pembuka
    const TINTA_DASHBOARD = 0.42; // tinta diredam agar angka & grafik tetap terbaca
    const JEDA_MASUK = 480;      // ms: konten pembuka memudar sebelum dashboard muncul
    const JEDA_INTRO_HILANG = 1100;
    const DURASI_HITUNG = 900;   // ms: animasi hitung angka KPI

    const fluid = (method, ...args) => {
        if (window.fluid && typeof window.fluid[method] === 'function') window.fluid[method](...args);
    };
    const introSudahDilihat = () => {
        try { return window.sessionStorage.getItem(KUNCI_INTRO) === '1'; } catch (e) { return false; }
    };

    const fmt = (v, dec) => Number(v).toFixed(dec).replace('.', ',');
    const pad2 = (n) => (n < 10 ? '0' + n : String(n));
    const cap = (t) => t.charAt(0).toUpperCase() + t.slice(1);
    const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
    const jeda = (ms) => new Promise((r) => setTimeout(r, ms));
    const joinId = (a) => {
        if (a.length <= 1) return a.join('');
        if (a.length === 2) return a[0] + ' dan ' + a[1];
        return a.slice(0, -1).join(', ') + ', dan ' + a[a.length - 1];
    };
    const reduceMotion = () => {
        try { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) { return false; }
    };

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
        if (s.key === 'ph') return v < s.min ? 'pH asam (' + val + '), di bawah batas aman.' : 'pH basa (' + val + '), di atas batas aman.';
        if (s.key === 'tds') return 'TDS tinggi (' + val + ' ppm), melebihi batas aman.';
        if (s.key === 'kekeruhan') return 'Kekeruhan tinggi (' + val + ' NTU), perlu penyaringan.';
        if (s.key === 'suhu') return 'Suhu ' + (v < s.min ? 'rendah' : 'tinggi') + ' (' + val + ' °C), di luar rentang normal.';
        return s.nama + ' (' + withUnit(s, v) + ') di luar batas aman.';
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
        else if (warn.length) text = 'Parameter normal; ' + joinId(warn.map((s) => s.lower + ' mendekati batas (' + withUnit(s, t[s.key]) + ')')) + ', perlu dipantau.';
        else text = 'Semua parameter normal, air layak.';
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
            const err = new Error('Server tidak merespons. Periksa jaringan, lalu coba lagi.');
            err.data = null;
            throw err;
        }
        let json = null;
        try { json = await res.json(); } catch (e) { /* bukan JSON */ }
        if (!res.ok) {
            const err = new Error((json && json.message) || 'Server tidak merespons. Periksa jaringan, lalu coba lagi.');
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
            { id: 'riwayat', label: 'Riwayat', icon: 'chart', view: 'riwayat', target: null },
            { id: 'evaluasi', label: 'Evaluasi', icon: 'table', view: 'evaluasi', target: null },
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
            sidebarOpen: true,
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

            // Layar pembuka & motion
            entered: lewatiIntro,
            introGone: lewatiIntro,
            introLeaving: false,
            introReady: false,
            chartRev: 0,

            init() {
                fluid('setInk', lewatiIntro ? TINTA_DASHBOARD : TINTA_INTRO);
                // Dua frame: pastikan keadaan awal (opacity 0) sudah dilukis sebelum reveal.
                requestAnimationFrame(() => requestAnimationFrame(() => { this.introReady = true; }));
            },

            destroy() {
                clearTimeout(toastTimer);
                uiTimers.forEach(clearTimeout);
            },

            /* ---------- Layar pembuka ---------- */
            get introBadge() {
                return (this.online ? 'Perangkat online' : 'Perangkat offline') + ' · ' + this.total + ' percobaan tercatat';
            },
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
            enterDashboard(view, navId) {
                if (this.entered || this.introLeaving) return;
                const rm = reduceMotion();
                this.introLeaving = true;
                try { window.sessionStorage.setItem(KUNCI_INTRO, '1'); } catch (e) { /* mode privat */ }
                fluid('burst', 14, 4);
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

            /* ---------- Motion grafik ---------- */
            // Memutar ulang animasi "menggambar" pada grafik/sparkline (dipicu chartRev).
            replayDraw(el, rev) {
                if (!this.entered || rev < 0) return;
                el.classList.remove('draw');
                void el.offsetWidth;
                el.classList.add('draw');
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
                if (!this.activeTrial || !this.activeTrial.name) return this.device.sampel;
                const n = this.activeTrial.name.trim();
                return n.toLowerCase().startsWith('air ') ? n : 'Air ' + n;
            },

            /* ---------- Perangkat & daya ---------- */
            get powerBusy() { return this.power === 'turning-on' || this.power === 'turning-off'; },
            get knobOn() { return this.power === 'on' || this.power === 'turning-on'; },
            get powerChecked() { return (this.power === 'on' || this.power === 'turning-off') ? 'true' : 'false'; },
            get powerLabel() {
                if (!this.online) return 'Tak terjangkau';
                return { on: 'Menyala', off: 'Mati', 'turning-on': 'Menyalakan...', 'turning-off': 'Mematikan...' }[this.power];
            },
            get powerClass() { return !this.online ? 't-dim' : (this.power === 'on' ? 't-ok' : 't-plain'); },
            get showOnline() { return this.online && this.power !== 'off'; },
            get showPowerOff() { return this.online && this.power === 'off'; },
            get syncDisabled() { return this.sync === 'loading' || this.power !== 'on'; },

            /* ---------- Status terkini ---------- */
            get statusBaik() { return this.curEval ? this.curEval.baik : true; },
            get statusSummary() {
                if (!this.curEval) return '';
                return this.curEval.baik
                    ? 'Semua parameter dalam batas aman'
                    : cap(joinId(this.curEval.bad.map((s) => s.lower))) + ' di luar batas aman';
            },
            get okCount() { return this.curEval ? SENSORS.length - this.curEval.bad.length : 0; },

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
                        lineColor: bad ? 'var(--bad)' : 'var(--accent)',
                        up: !same && delta > 0,
                        deltaTxt: same ? '0' : fmt(Math.abs(delta), s.dec),
                        deltaWord: !prev ? 'percobaan pertama'
                            : (same ? 'sama dengan P' + pad2(prev.no) : (delta > 0 ? 'naik dari P' : 'turun dari P') + pad2(prev.no)),
                        spark: data.map((t, i) => xPos(i).toFixed(2) + ',' + sy(t[s.key]).toFixed(2)).join(' '),
                        dotLeft: xPos(n - 1).toFixed(2),
                        dotTop: (sy(v) / 40 * 100).toFixed(2),
                        sparkAria: 'Tren ' + s.nama + ' per percobaan: ' + data.map((t) => fmt(t[s.key], s.dec)).join('; '),
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
                        label: fmt(v, s.dec) + (lv === 'bad' ? arrowFor(s, v, lv) : ''),
                        aria: 'Percobaan ' + t.no + ', ' + s.nama + ' ' + withUnit(s, v) + ', ' + LV[lv].label,
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
                            title: 'Percobaan ' + pad2(t.no), date: t.tanggal + ', ' + t.jam,
                            sensor: s.nama, val: withUnit(s, t[s.key]), status: LV[lv].label, cls: 'lv-' + lv,
                        };
                    }
                }

                const unit = s.satuan ? ' (' + s.satuan + ')' : '';
                return {
                    id: s.key + (big ? '-b' : '-s'),
                    key: s.key,
                    gradId: 'area-' + s.key + (big ? '-b' : '-s'),
                    title: (big ? s.nama + ' per percobaan' : s.nama) + unit,
                    rangeTxt: s.range,
                    h: big ? 300 : 190,
                    ticks: s.ticks.map((v) => ({ v, top: this.yPos(s, v).toFixed(2), label: fmt(v, 0) })),
                    band: { top: bandTop.toFixed(2), height: (this.yPos(s, lo) - bandTop).toFixed(2) },
                    line: pts.join(' '),
                    area: pts.join(' ') + ' ' + x1 + ',100 ' + x0 + ',100',
                    points, tip,
                    xlabels: data.map((t, i) => ({ no: t.no, left: xPos(i).toFixed(2), label: big ? 'Percobaan ' + t.no : 'P' + pad2(t.no) })),
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
                        hay: ('percobaan ' + t.no + ' ' + pad2(t.no) + ' ' + t.tanggal + ' ' + t.jam + ' ' + e.text + ' ' + (e.baik ? 'baik' : 'buruk')).toLowerCase(),
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
                    { id: 'semua', label: 'Semua', count: this.total },
                    { id: 'baik', label: 'Baik', count: this.baikCount },
                    { id: 'buruk', label: 'Buruk', count: this.burukCount },
                ];
            },
            get sensorHeaders() {
                return SENSORS.map((s) => ({ key: s.key, label: s.nama + (s.satuan ? ' (' + s.satuan + ')' : '') }));
            },

            /* ---------- Trial helpers ---------- */
            fmtAvg(v, dec) { return fmt(v, dec); },
            trialAvgClass(key, trial) {
                if (!trial.rata_rata) return '';
                const s = SENSORS.find((x) => x.key === key);
                if (!s) return '';
                return level(s, trial.rata_rata[key]);
            },
            trialStatusBaik(trial) {
                return trialAvgBaik(SENSORS, trial.rata_rata);
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
                    this.trialNameError = 'Nama trial tidak boleh kosong.';
                    return;
                }
                this.trialNameError = '';
                this.trialStarting = true;
                try {
                    const json = await kirim(this.urls.trial_start, { name });
                    this.activeTrial = json.trial;
                    // Jangan reset trials — biarkan data lama tampil sampai sync berikutnya
                    // supaya dashboard tidak kosong. Trials akan di-refresh saat doSync().
                    this.terapkanPerangkat(json.perangkat);
                    this.showInputDetected = false;
                    this.trialNameInput = '';
                    this.newTrialStarted = true; // flag: trial baru, data di dashboard adalah sisa trial lama
                    this.showToast({ kind: 'success', title: 'Trial "' + name + '" dimulai', msg: 'Tekan Sinkronkan untuk mengambil data percobaan pertama.' }, 6000);
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
                    this.showToast({ kind: 'success', title: 'Trial "' + json.trial.name + '" selesai', msg: 'Rata-rata telah dihitung dan disimpan di Evaluasi.' }, 5000);
                    // Reset state aktif tapi PERTAHANKAN trials lama di UI supaya tidak blank
                    this.activeTrial = null;
                    this.newTrialStarted = false;
                    // Tampilkan popup input nama trial baru
                    this.showInputDetected = true;
                } catch (e) {
                    this.showToast({ kind: 'error', retry: 'sync', title: 'Gagal mengakhiri trial', msg: e.message });
                }
            },

            async refreshHistory() {
                this.historyLoading = true;
                try {
                    const json = await kirim(this.urls.trial_history, null, 'GET');
                    this.completedTrials = json.trials;
                } catch (e) {
                    this.showToast({ kind: 'error', retry: 'sync', title: 'Gagal memuat histori', msg: e.message });
                } finally {
                    this.historyLoading = false;
                }
            },

            /* ---------- Notifikasi ---------- */
            get notifs() {
                const list = [];
                if (this.cur && this.device.terakhir_sinkron) {
                    list.push({ id: 'sync', kind: 'ok', title: 'Sinkronisasi terakhir berhasil', sub: 'Data Percobaan ' + pad2(this.cur.no) + ' diterima · ' + this.lastSync, no: null });
                }
                this.trials.slice().reverse().forEach((t) => {
                    if (list.length >= 6) return;
                    const e = nilai(t);
                    if (e.bad.length) list.push({ id: 'p' + t.no, kind: 'bad', title: 'Percobaan ' + pad2(t.no) + ': air buruk', sub: e.text, no: t.no });
                    else if (e.warn.length) list.push({ id: 'p' + t.no, kind: 'warn', title: 'Percobaan ' + pad2(t.no) + ': perlu dipantau', sub: e.text, no: t.no });
                });
                return list;
            },
            get unread() { return this.notifRead ? 0 : this.notifs.length; },
            get bellAria() { return this.unread > 0 ? 'Notifikasi, ' + this.unread + ' belum dibaca' : 'Notifikasi'; },
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
                if (this.view === 'riwayat') return 'Riwayat Percobaan';
                if (this.view === 'evaluasi') return 'Evaluasi';
                return 'Dashboard Monitoring Kualitas Air';
            },
            get viewSub() {
                if (this.view === 'riwayat') return 'Daftar dan catatan otomatis untuk setiap percobaan' + (this.activeTrial ? ' — ' + this.activeTrial.name : '') + '.';
                if (this.view === 'evaluasi') return 'Histori semua trial air minum yang telah selesai.';
                if (!this.cur) return 'Sampel ' + this.sampleLabel + ' · belum ada percobaan';
                return 'Sampel ' + this.sampleLabel + ' · ' + this.total + ' percobaan · data terkini dari Percobaan ' + this.curNoPad;
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
            pickNav(n) { this.go(n.view, n.id, n.target); },
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
                if (this.toast && this.toast.retry === 'power') this.togglePower(); else this.doSync();
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
                fluid('setTempo', 2.6);
                try {
                    const [json] = await Promise.all([kirim(this.urls.sinkron), jeda(JEDA_MIN)]);
                    const sebelum = this.trials.length;
                    this.trials = json.percobaan;
                    this.chartRev++;
                    fluid('burst', 10, 3);
                    this.terapkanPerangkat(json.perangkat);
                    if (json.active_trial) this.activeTrial = json.active_trial;
                    this.newTrialStarted = false; // flag trial baru dibersihkan setelah sync
                    if (this.trials.length > sebelum) this.notifRead = false;
                    const baru = this.trials.length - sebelum;
                    this.showToast({
                        kind: 'success',
                        title: 'Data berhasil disinkronkan',
                        msg: baru > 0 ? baru + ' percobaan baru diterima dari alat.' : 'Pembacaan 4 sensor terbaru sudah diterima.',
                    }, 4000);
                } catch (e) {
                    if (e.data && e.data.perangkat) this.terapkanPerangkat(e.data.perangkat);
                    this.showToast({ kind: 'error', retry: 'sync', title: 'Sinkronisasi gagal', msg: e.message });
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
                        this.showToast({ kind: 'success', title: 'Alat dinyalakan', msg: 'Sensor mulai membaca lagi. Sinkronkan untuk mengambil data terbaru.' }, 4000);
                    } else {
                        this.showToast({ kind: 'info', title: 'Alat dimatikan', msg: 'Sensor berhenti membaca data sampai alat dinyalakan lagi.' });
                    }
                } catch (e) {
                    this.power = turningOn ? 'off' : 'on';
                    if (e.data && e.data.perangkat) this.device = e.data.perangkat;
                    this.showToast({ kind: 'error', retry: 'power', title: 'Perintah tidak sampai ke alat', msg: e.message });
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

    // x-countup="'7,2'": angka berhitung halus ke nilai baru (format desimal koma).
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
                const target = parseFloat(str.replace(',', '.'));
                const dec = (str.split(',')[1] || '').length;
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

    // Riak air kecil di titik tekan pada tombol (.btn / .pill).
    function pasangRiakTombol() {
        document.addEventListener('pointerdown', (e) => {
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
    });
})();
