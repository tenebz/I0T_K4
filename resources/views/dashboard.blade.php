<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AquaSense — Water Quality Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    <script id="data-awal" type="application/json">@json($awal)</script>
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
    <script defer src="{{ asset('js/fluid.js') }}?v={{ filemtime(public_path('js/fluid.js')) }}"></script>
    <script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>
</head>
<body>
{{-- ============ FLUID BACKGROUND (WebGL) ============ --}}
<canvas id="fluid-canvas" class="fluid-canvas" aria-hidden="true"></canvas>
<div class="scrim scrim-intro" aria-hidden="true"></div>
<div class="scrim scrim-dash" aria-hidden="true"></div>

<div x-data="dashboard" x-cloak class="root" :class="{ 'is-entered': entered, 'is-intro-leaving': introLeaving }" x-effect="document.documentElement.classList.toggle('is-dashboard', entered)">

    {{-- ============ OPENING SCREEN ============ --}}
    <template x-if="!introGone">
        <section class="intro" :class="{ 'is-ready': introReady, 'is-leaving': introLeaving }" aria-labelledby="intro-title" @keydown.enter.window="if (document.activeElement === document.body) enterDashboard()">
            <header class="intro-nav lm-reveal" style="--d: 150ms; --from: translateY(calc(-0.75 * var(--u)))">
                <a href="#ringkasan" class="intro-brand" @click.prevent="enterDashboard('dashboard', 'ringkasan')">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2.5 9c2.5 0 2.5 4.2 5 4.2S10 9 12 9s2.5 4.2 5 4.2S19.5 9 21.5 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M2.5 15c2.5 0 2.5 4.2 5 4.2S10 15 12 15s2.5 4.2 5 4.2S19.5 15 21.5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
                    </svg>
                    <span>AquaSense</span>
                </a>
                <nav class="intro-links" aria-label="Dashboard shortcuts">
                    <a href="#ringkasan" @click.prevent="enterDashboard('dashboard', 'ringkasan')">Dashboard</a>
                    <a href="#riwayat" @click.prevent="enterDashboard('riwayat', 'riwayat')">History</a>
                    <a href="#evaluasi" @click.prevent="enterDashboard('evaluasi', 'evaluasi')">Evaluation</a>
                </nav>
                <button type="button" class="pill" :class="{ 'is-loading': introLeaving }" :aria-busy="introLeaving.toString()" @click="enterDashboard()">Go to Dashboard</button>
            </header>

            <div class="intro-col">
                <p class="intro-badge lm-reveal" style="--d: 320ms">
                    <span class="dot" :class="online ? 'dot-ok' : 'dot-bad'" aria-hidden="true"></span>
                    <span x-text="introBadge"></span>
                </p>
                <h1 id="intro-title" class="intro-title" x-init="splitWords($el, 480, 85)">Integrated Real Time Water Quality System</h1>
                <p class="intro-sub" x-init="splitWords($el, 1150, 22)">pH, TDS, turbidity, and temperature from IoT sensors, evaluated automatically on every trial.</p>

                <div class="intro-cta lm-reveal" style="--d: 1450ms">
                    <button type="button" class="pill pill-lg" x-ref="introEnter" :class="{ 'is-loading': introLeaving }" :aria-busy="introLeaving.toString()" @click="enterDashboard()">
                        <span x-text="introLeaving ? 'Opening…' : 'Go to Dashboard'">Go to Dashboard</span>
                        <svg x-show="!introLeaving" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                        <svg x-show="introLeaving" class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.55"/></svg>
                    </button>
                </div>
            </div>

            <footer class="intro-foot lm-reveal" style="--d: 1650ms">
                © 2026 AquaSense - Made with ❤️ by Erkham, faishal, Najwa, Ratri, Pebri, Sultan, Iim, Galih, Via
            </footer>
        </section>
    </template>

    {{-- ============ INPUT DETECTED MODAL ============ --}}
    <template x-if="showInputDetected && entered">
        <div class="overlay" @click.self.prevent>
            <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="judul-input-detected" tabindex="-1" style="width: 480px">
                <div class="dialog-head" style="border-bottom: none; padding-bottom: 0">
                    <div style="width: 100%; text-align: center">
                        <div style="margin: 0 auto 16px; width: 72px; height: 72px; border-radius: 22px; background: var(--accent-soft); color: var(--accent); display: flex; align-items: center; justify-content: center">
                            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.7s-6 6.6-6 11.3a6 6 0 0 0 12 0c0-4.7-6-11.3-6-11.3z"/></svg>
                        </div>
                        <div class="chip chip-online" style="margin-bottom: 12px"><span class="dot dot-ok" aria-hidden="true"></span>Input Detected</div>
                        <h2 id="judul-input-detected" class="dialog-title" style="font-size: 20px">IoT sensor detected water</h2>
                        <p class="page-sub" style="margin-top: 8px">Enter a trial name to start a new trial session.</p>
                    </div>
                </div>
                <div style="padding: 16px 26px 22px">
                    <label for="trial-name-input" style="font-size: 14px; font-weight: 700; color: var(--text-2); display: block; margin-bottom: 6px">Trial Name</label>
                    <input id="trial-name-input" type="text" x-model="trialNameInput" placeholder="e.g. Morning Drinking Water Test" @keydown.enter="submitTrialName()"
                        style="width: 100%; height: 44px; padding: 0 14px; border: 1px solid var(--input-line); border-radius: 10px; font-size: 15px; color: var(--text); background: var(--bg)">
                    <template x-if="trialNameError">
                        <p style="color: var(--bad); font-size: 13px; margin: 6px 0 0" x-text="trialNameError"></p>
                    </template>
                    <button type="button" class="btn-primary btn" style="width: 100%; margin-top: 14px; justify-content: center" :class="{ 'is-loading': trialStarting }" :aria-busy="trialStarting.toString()" @click="submitTrialName()" :disabled="trialStarting">
                        <template x-if="trialStarting">
                            <span style="display: contents">
                                <svg class="spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.55"/></svg>
                                <span>Starting…</span>
                            </span>
                        </template>
                        <template x-if="!trialStarting">
                            <span>Start Trial</span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <div class="app" :class="{ 'is-in': entered, 'is-out': dashLeaving }" :inert="!entered" :aria-hidden="(!entered).toString()">
    {{-- ============ TOP BAR ============ --}}
    <header class="topbar">
        <button type="button" class="icon-btn btn" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Show or hide the menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a href="#" class="topbar-brand" @click.prevent="backToIntro()" aria-label="AquaSense — back to the opening screen">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M2.5 9c2.5 0 2.5 4.2 5 4.2S10 9 12 9s2.5 4.2 5 4.2S19.5 9 21.5 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M2.5 15c2.5 0 2.5 4.2 5 4.2S10 15 12 15s2.5 4.2 5 4.2S19.5 15 21.5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
            </svg>
            <span>AquaSense</span>
        </a>

        <div class="search">
            <label for="cari-percobaan" class="sr-only">Search trials</label>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="cari-percobaan" type="search" :value="query" @input="onQuery($event.target.value)" placeholder="Search trials, dates, or evaluations" autocomplete="off">
        </div>

        <div class="spacer"></div>

        <div class="topbar-right">
            <template x-if="showPowerOff">
                <span class="chip chip-off"><span class="dot-ring" aria-hidden="true"></span>Device off</span>
            </template>
            <template x-if="showOnline">
                <span class="chip chip-online"><span class="dot dot-ok" aria-hidden="true"></span>Device online</span>
            </template>
            <template x-if="!online">
                <span class="chip chip-offline"><span class="dot dot-bad" aria-hidden="true"></span>Device offline</span>
            </template>

            <div class="notif" @keydown.escape="notifOpen = false" @click.outside="notifOpen = false">
                <button type="button" class="icon-btn bell btn" @click="notifOpen = !notifOpen" :aria-expanded="notifOpen.toString()" aria-haspopup="true" :aria-label="bellAria">
                    <svg width="23" height="23" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a6 6 0 0 0-6 6v3.6l-1.7 3.1A1 1 0 0 0 5.2 16h13.6a1 1 0 0 0 .9-1.3L18 11.6V8a6 6 0 0 0-6-6zm-2.5 15.5a2.5 2.5 0 0 0 5 0z"/></svg>
                    <template x-if="unread > 0">
                        <span class="notif-count" aria-hidden="true" x-text="unread"></span>
                    </template>
                </button>
                <template x-if="notifOpen">
                    <div class="popover pop" role="region" aria-label="Notifications">
                        <div class="popover-head">
                            <span class="popover-title">Notifications</span>
                            <button type="button" class="link-btn" @click="notifRead = true">Mark all as read</button>
                        </div>
                        <template x-for="m in notifs" :key="m.id">
                            <button type="button" class="notif-item" :class="{ unread: !notifRead }" @click="openNotif(m)">
                                <span class="notif-icon" :class="m.kind" aria-hidden="true">
                                    <svg x-show="m.kind === 'bad'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 6v8"/><path d="M12 18h.01"/></svg>
                                    <svg x-show="m.kind === 'warn'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2"/></svg>
                                    <svg x-show="m.kind === 'ok'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span style="min-width: 0">
                                    <span class="notif-title" x-text="m.title"></span>
                                    <span class="notif-sub" x-text="m.sub"></span>
                                </span>
                            </button>
                        </template>
                        <template x-if="notifs.length === 0">
                            <div class="notif-sub" style="padding: 16px 18px">No notifications yet.</div>
                        </template>
                        <a href="#riwayat-page" class="popover-foot" @click.prevent="go('riwayat', 'riwayat', null)">View all trials</a>
                    </div>
                </template>
            </div>
        </div>
    </header>

    <div class="layout">

        {{-- ============ SIDEBAR ============ --}}
        <template x-if="sidebarOpen">
            <nav class="sidebar" aria-label="Main menu">
                <template x-for="n in navs" :key="n.id">
                    <div class="nav-wrap">
                        <span x-show="n.active" class="nav-indicator" aria-hidden="true"></span>
                        <a class="nav" :class="{ active: n.active }" :href="'#' + n.id" @click.prevent="pickNav(n)" :aria-current="n.active ? 'page' : 'false'">
                            <svg x-show="n.icon === 'dash'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 12l4-3"/><path d="M7 15h10"/></svg>
                            <svg x-show="n.icon === 'chart'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-5 3 3 5-7"/></svg>
                            <svg x-show="n.icon === 'table'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z"/><path d="M8 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="m9 14 2 2 4-4"/></svg>
                            <span class="nav-label" x-text="n.label"></span>
                            <template x-if="n.badge">
                                <span class="nav-badge" x-text="n.badge" :aria-label="n.badge + ' poor trials'"></span>
                            </template>
                        </a>
                    </div>
                </template>

                <div class="divider"></div>
                <div class="side-heading">Device</div>
                <div class="device-box">
                    <div class="kv"><span>Status</span><strong :class="online ? 't-ok' : 't-bad'" x-text="online ? 'Online' : 'Offline'"></strong></div>
                    <div class="kv"><span>Power</span><strong :class="powerClass" x-text="powerLabel"></strong></div>
                    <div class="kv"><span>Sample</span><strong x-text="activeTrial ? activeTrial.name : device.sampel"></strong></div>
                    <div class="kv"><span>Active Sensors</span><strong x-text="sensorList.length"></strong></div>
                    <div class="kv"><span>Last Sync</span><strong x-text="lastSync"></strong></div>
                </div>
            </nav>
        </template>

        {{-- ============ CONTENT ============ --}}
        <main class="content">

            <div id="ringkasan" class="page-head">
                <div>
                    <template x-if="view !== 'dashboard'">
                        <nav class="breadcrumb" aria-label="Breadcrumb">
                            <a href="#ringkasan" @click.prevent="go('dashboard', 'ringkasan', null)">Dashboard</a>
                            <span aria-hidden="true">/</span>
                            <span aria-current="page" x-text="viewTitle"></span>
                        </nav>
                    </template>
                    <h1 id="judul-view" class="page-title" tabindex="-1" x-text="viewTitle"></h1>
                    <p class="page-sub" x-text="viewSub"></p>
                </div>
                <div class="head-actions">
                    <span class="sync-info">Last sync: <strong x-text="lastSync"></strong></span>
                    <div class="power-box">
                        <span id="label-alat" class="power-title">Device</span>
                        <button type="button" role="switch" class="switch" :class="{ on: knobOn }"
                                :aria-checked="powerChecked" aria-labelledby="label-alat status-alat"
                                :aria-busy="powerBusy.toString()" :disabled="!online || powerBusy" @click="togglePower()">
                            <span class="track" aria-hidden="true"></span>
                            <span class="knob" aria-hidden="true">
                                <svg x-show="powerBusy" class="spin" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3B4250" stroke-width="3" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-6.2-8.55"/></svg>
                            </span>
                        </button>
                        <span id="status-alat" class="power-label" :class="powerClass" x-text="powerLabel"></span>
                    </div>

                    {{-- Sync dropdown button --}}
                    <div class="sync-dropdown" @click.outside="syncDropdownOpen = false" style="position: relative">
                        <button type="button" class="btn-primary btn btn-sync" :class="{ 'is-loading': sync === 'loading' }" @click="syncDropdownOpen = !syncDropdownOpen" :disabled="syncDisabled" :aria-busy="(sync === 'loading').toString()" :aria-expanded="syncDropdownOpen.toString()" aria-haspopup="menu" style="gap: 6px">
                            <svg class="sync-icon" :class="{ spin: sync === 'loading' }" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                            <span x-text="sync === 'loading' ? 'Syncing…' : 'Sync'">Sync</span>
                            <svg class="chev" :class="{ open: syncDropdownOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <template x-if="syncDropdownOpen">
                            <div class="popover pop" style="right: 0; top: 52px; width: 220px; z-index: 20" role="menu">
                                <button type="button" class="notif-item" role="menuitem" @click="doSync(); syncDropdownOpen = false" :disabled="sync === 'loading'">
                                    <span class="notif-icon ok" aria-hidden="true" style="width: 32px; height: 32px">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                                    </span>
                                    <span style="min-width: 0">
                                        <span class="notif-title">Sync now</span>
                                        <span class="notif-sub">Fetch the latest readings</span>
                                    </span>
                                </button>
                                <template x-if="activeTrial">
                                    <button type="button" class="notif-item" role="menuitem" @click="endTrial(); syncDropdownOpen = false">
                                        <span class="notif-icon bad" aria-hidden="true" style="width: 32px; height: 32px">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        </span>
                                        <span style="min-width: 0">
                                            <span class="notif-title">End This Trial</span>
                                            <span class="notif-sub">Close the active trial</span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Device offline banner --}}
            <template x-if="!online">
                <div class="banner banner-warn" role="status">
                    <span class="banner-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 2l20 20"/><path d="M8.5 16.4a5 5 0 0 1 7 0"/><path d="M5 12.9a10 10 0 0 1 5.2-2.8"/><path d="M19 12.9a10 10 0 0 0-2.4-1.7"/><path d="M12 20h.01"/></svg></span>
                    <div class="banner-body">
                        <div class="banner-title">Device offline. The data below is the last reading (<span x-text="lastSync"></span>).</div>
                        <div class="banner-text">Turn the device on, make sure it is connected to Wi-Fi, then sync again.</div>
                    </div>
                    <button type="button" class="btn-warn btn" :class="{ 'is-loading': sync === 'loading' }" @click="power === 'on' ? doSync() : togglePower()" :disabled="sync === 'loading'" :aria-busy="(sync === 'loading').toString()">
                        <svg x-show="sync === 'loading'" class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.55"/></svg>
                        <span x-text="sync === 'loading' ? 'Syncing…' : 'Retry sync'">Retry sync</span>
                    </button>
                </div>
            </template>

            {{-- Device powered-off banner --}}
            <template x-if="showPowerOff">
                <div class="banner banner-plain" role="status">
                    <span class="banner-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 3v8"/><path d="M6.3 6.3a8 8 0 1 0 11.4 0"/></svg></span>
                    <div class="banner-body">
                        <div class="banner-title">Device is turned off. Sensors are not reading data.</div>
                        <div class="banner-text">Values below are the last reading (<span x-text="lastSync"></span>). Sync becomes available again once the device is on.</div>
                    </div>
                    <button type="button" class="btn-primary sm btn" @click="togglePower()">Turn on device</button>
                </div>
            </template>

            {{-- No data yet --}}
            <template x-if="!hasData && !activeTrial">
                <section class="card no-data" aria-label="No data yet">
                    <div class="empty-title">No active trial</div>
                    <div class="empty-text">The IoT sensor will detect water and start a new trial automatically.</div>
                </section>
            </template>

            {{-- ============ VIEW: DASHBOARD ============ --}}
            <template x-if="view === 'dashboard'">
                <div class="view" :class="viewClass">

                    {{-- Status --}}
                    <template x-if="hasData">
                        <section class="card status" :class="{ 'is-bad': !statusBaik }" aria-label="Water quality status">
                            <div class="status-main">
                                <span class="status-icon" aria-hidden="true">
                                    <svg x-show="statusBaik" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    <svg x-show="!statusBaik" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                </span>
                                <div style="min-width: 0">
                                    <div class="status-eyebrow"><span x-text="sampleLabel"></span> status · Trial <span x-text="curNoPad"></span></div>
                                    <div class="status-line">
                                        <span class="status-label" x-text="statusBaik ? 'GOOD' : 'POOR'"></span>
                                        <span class="status-summary" x-text="statusSummary"></span>
                                    </div>
                                    <div class="status-meta"><span x-text="cur.tanggal"></span>, <span x-text="cur.jam"></span> WIB · <span x-text="okCount"></span> of <span x-text="sensorList.length"></span> parameters normal</div>
                                </div>
                            </div>
                            <button type="button" class="btn-outline btn" @click="openDetail(cur.no)">View trial details</button>
                        </section>
                    </template>

                    {{-- Sensor KPIs --}}
                    <template x-if="hasData">
                        <section id="sensor" class="kpis" :class="{ 'is-syncing': sync === 'loading' }" aria-label="Latest sensor readings">
                            <template x-for="s in sensorCards" :key="s.key">
                                <article class="card kpi" :class="{ 'is-bad': s.isBad }">
                                    <div class="kpi-top">
                                        <div>
                                            <h3 class="kpi-name" x-text="s.nama"></h3>
                                            <div class="kpi-value">
                                                <span class="kpi-num" x-countup="entered ? s.valTxt : null"></span>
                                                <span class="kpi-unit" x-text="s.satuan"></span>
                                            </div>
                                        </div>
                                        <span class="tile" :class="s.tile" aria-hidden="true">
                                            <svg x-show="s.key === 'ph'" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5c-.3 0-.6.1-.8.4C9.6 4.9 5.5 10 5.5 14a6.5 6.5 0 0 0 13 0c0-4-4.1-9.1-5.7-11.1a1 1 0 0 0-.8-.4z"/></svg>
                                            <svg x-show="s.key === 'tds'" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6v2h-1v5.3l5.6 9.4A2.2 2.2 0 0 1 17.7 22H6.3a2.2 2.2 0 0 1-1.9-3.3L10 9.3V4H9z"/></svg>
                                            <svg x-show="s.key === 'kekeruhan'" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M2 7c2-1.5 4-1.5 6 0s4 1.5 6 0 4-1.5 6 0"/><path d="M2 12c2-1.5 4-1.5 6 0s4 1.5 6 0 4-1.5 6 0"/><path d="M2 17c2-1.5 4-1.5 6 0s4 1.5 6 0 4-1.5 6 0"/></svg>
                                            <svg x-show="s.key === 'suhu'" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M10 2a3 3 0 0 0-3 3v9.1a5 5 0 1 0 8 0V5a3 3 0 0 0-3-3zm1 13.2V8h2v7.2a3 3 0 1 1-2 0z" transform="translate(-1 0)"/></svg>
                                        </span>
                                    </div>
                                    <div class="kpi-tags">
                                        <template x-if="!s.isBad">
                                            <span class="tag tag-ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Normal</span>
                                        </template>
                                        <template x-if="s.isBad">
                                            <span class="tag tag-bad"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 6v8"/><path d="M12 18h.01"/></svg>Out of range</span>
                                        </template>
                                        <span class="kpi-range">Normal <span x-text="s.rangeTxt"></span></span>
                                    </div>
                                    <div class="kpi-foot">
                                        <span class="kpi-delta">
                                            <svg x-show="s.up" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>
                                            <svg x-show="!s.up" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/></svg>
                                            <span><strong x-text="s.deltaTxt"></strong> <span x-text="s.deltaWord"></span></span>
                                        </span>
                                        <div class="spark" role="img" :aria-label="s.sparkAria" x-effect="replayDraw($el, chartRev)">
                                            <svg viewBox="0 0 100 40" preserveAspectRatio="none" aria-hidden="true">
                                                <polyline class="spark-line" :points="s.spark" fill="none" :style="{ stroke: s.lineColor }" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"/>
                                            </svg>
                                            <span class="spark-dot" aria-hidden="true" :style="{ left: s.dotLeft + '%', top: s.dotTop + '%', background: s.lineColor }"></span>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </section>
                    </template>

                    {{-- History chart --}}
                    <template x-if="hasData">
                        <section id="riwayat-chart" class="card chart-card" :class="{ 'is-syncing': sync === 'loading' }" :aria-busy="(sync === 'loading').toString()" :aria-label="sampleLabel + ' history chart'">
                            <div class="section-head">
                                <div>
                                    <h2 class="section-title"><span x-text="sampleLabel"></span> History</h2>
                                    <p class="section-sub">Results for each trial. Hover or focus a point for details.</p>
                                </div>
                                <div class="controls">
                                    <div class="select-wrap" :style="{ visibility: single ? 'visible' : 'hidden' }">
                                        <label for="pilih-sensor" class="sr-only">Select sensor</label>
                                        <select id="pilih-sensor" x-model="sensorKey" @change="hover = null">
                                            @foreach ($awal['sensor'] as $kunci => $sensor)
                                                <option value="{{ $kunci }}">{{ $sensor['nama'] }}</option>
                                            @endforeach
                                        </select>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                    </div>
                                    <div class="segmented" role="group" aria-label="Chart view mode">
                                        <button type="button" class="seg btn" :aria-pressed="single.toString()" @click="mode = 'single'; hover = null">Single sensor</button>
                                        <button type="button" class="seg btn" :aria-pressed="(!single).toString()" @click="mode = 'all'; hover = null">All (2×2)</button>
                                    </div>
                                </div>
                            </div>
                            <div class="legend">
                                <span class="legend-item"><span class="lg-band" aria-hidden="true"></span>Normal range</span>
                                <span class="legend-item"><span class="lg-dot" aria-hidden="true"></span>Normal</span>
                                <span class="legend-item"><span class="lg-dot warn" aria-hidden="true"></span>Near limit</span>
                                <span class="legend-item"><span class="lg-diamond" aria-hidden="true"></span>Out of range (↑ / ↓)</span>
                            </div>
                            <div class="charts" :class="{ all: !single }">
                                <template x-for="c in charts" :key="c.id">
                                    <div class="chart">
                                        <div class="chart-head">
                                            <h3 class="chart-title" x-text="c.title"></h3>
                                            <span class="chart-range">Normal <span x-text="c.rangeTxt"></span></span>
                                        </div>
                                        <div class="chart-row">
                                            <div class="y-axis" :style="{ height: c.h + 'px' }">
                                                <template x-for="t in c.ticks" :key="t.v">
                                                    <div class="y-tick" :style="{ top: t.top + '%' }" x-text="t.label"></div>
                                                </template>
                                            </div>
                                            <div class="plot" :style="{ height: c.h + 'px' }" @mouseleave="hover = null" x-effect="replayDraw($el, chartRev)">
                                                <template x-for="t in c.ticks" :key="t.v">
                                                    <div class="gridline" aria-hidden="true" :style="{ top: t.top + '%' }"></div>
                                                </template>
                                                <div class="band" aria-hidden="true" :style="{ top: c.band.top + '%', height: c.band.height + '%' }"></div>
                                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                                    <defs>
                                                        <linearGradient :id="c.gradId" x1="0" y1="0" x2="0" y2="1">
                                                            <stop offset="0" style="stop-color: var(--accent); stop-opacity: 0.22"/>
                                                            <stop offset="1" style="stop-color: var(--accent); stop-opacity: 0"/>
                                                        </linearGradient>
                                                    </defs>
                                                    <polygon class="chart-area" :points="c.area" :fill="'url(#' + c.gradId + ')'"/>
                                                    <polyline class="chart-line" :points="c.line" fill="none" style="stroke: var(--accent)" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"/>
                                                </svg>
                                                <template x-for="p in c.points" :key="p.id">
                                                    <div class="pt-label" aria-hidden="true" :class="p.lv" :style="{ left: p.left + '%', top: p.top + '%', '--i': p.i }" x-text="p.label"></div>
                                                </template>
                                                <template x-for="p in c.points" :key="p.id">
                                                    <button type="button" class="pt" :aria-label="p.aria" :style="{ left: p.left + '%', top: p.top + '%', '--i': p.i }"
                                                            @mouseenter="hover = p.id" @focus="hover = p.id" @blur="hover = null">
                                                        <span x-show="p.lv === 'bad'" class="pt-diamond"></span>
                                                        <span x-show="p.lv !== 'bad'" class="pt-dot" :class="{ warn: p.lv === 'warn' }"></span>
                                                    </button>
                                                </template>
                                                <template x-if="c.tip">
                                                    <div class="tooltip" role="status" :style="{ left: c.tip.left + '%', top: c.tip.top + '%', transform: 'translate(' + c.tip.shift + ', calc(-100% - 40px))' }">
                                                        <div class="tooltip-head"><span x-text="c.tip.title"></span> · <span x-text="c.tip.date"></span></div>
                                                        <div class="tooltip-row"><span x-text="c.tip.sensor"></span><span x-text="c.tip.val"></span></div>
                                                        <div class="tooltip-status" :class="c.tip.cls" x-text="c.tip.status"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="chart-row">
                                            <div class="x-gutter"></div>
                                            <div class="x-axis">
                                                <template x-for="x in c.xlabels" :key="x.no">
                                                    <div class="x-label" :style="{ left: x.left + '%' }" x-text="x.label"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </template>

                    {{-- History & evaluation call to action --}}
                    <template x-if="hasData">
                        <section class="card cta" aria-label="Evaluation summary">
                            <div class="cta-stats">
                                <span class="cta-title">Evaluation of <span x-text="total"></span> trials</span>
                                <span class="cta-stat ok"><span class="dot dot-ok" aria-hidden="true"></span><span x-text="baikCount"></span> good</span>
                                <span class="cta-stat bad"><span class="diamond" aria-hidden="true"></span><span x-text="burukCount"></span> poor</span>
                                <span class="sync-info">Pass rate <span x-text="pct"></span>%</span>
                            </div>
                            <a href="#riwayat-page" class="btn-soft btn" @click.prevent="go('riwayat', 'riwayat', null)">Open history
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                            </a>
                        </section>
                    </template>
                </div>
            </template>

            {{-- ============ VIEW: HISTORY (trials of the active session) ============ --}}
            <template x-if="view === 'riwayat'">
                <div class="view" :class="viewClass">
                    <section id="riwayat-page" class="card eval" aria-label="Trial history">
                        <div class="section-head">
                            <div>
                                <h2 class="section-title sm">Trial list
                                    <template x-if="activeTrial">
                                        <span style="color: var(--accent)"> — <span x-text="activeTrial.name"></span></span>
                                    </template>
                                </h2>
                                <p class="section-sub">Click a row to open trial details. New trials are added when you sync.</p>
                            </div>
                            <div class="segmented" role="group" aria-label="Filter by status">
                                <template x-for="f in filters" :key="f.id">
                                    <button type="button" class="seg lg btn" :aria-pressed="(filter === f.id).toString()" @click="filter = f.id">
                                        <span x-text="f.label"></span><span class="seg-count" x-text="f.count"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="stats">
                            <div class="stat"><div class="stat-label">Total trials</div><div class="stat-num" x-text="total"></div></div>
                            <div class="stat"><div class="stat-label ok"><span class="dot dot-ok" aria-hidden="true"></span>Good</div><div class="stat-num" x-text="baikCount"></div></div>
                            <div class="stat"><div class="stat-label bad"><span class="diamond" aria-hidden="true"></span>Poor</div><div class="stat-num" x-text="burukCount"></div></div>
                            <div class="stat">
                                <div class="stat-label">Eligibility / Pass rate</div>
                                <div class="stat-bar">
                                    <span class="stat-num" style="margin: 0"><span x-text="pct"></span>%</span>
                                    <span class="bar bar-grow" x-effect="replayDraw($el, chartRev)" role="img" :aria-label="'Pass rate ' + pct + ' percent'"><span :style="{ width: pct + '%' }"></span></span>
                                </div>
                            </div>
                        </div>

                        <template x-if="q">
                            <div class="search-note">
                                <span>Showing <span x-text="rows.length"></span> of <span x-text="total"></span> trials for "<span x-text="query"></span>"</span>
                                <button type="button" class="link-btn" @click="query = ''">Clear search</button>
                            </div>
                        </template>

                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">Trial No.</th>
                                    <th scope="col">Date &amp; Time</th>
                                    <template x-for="h in sensorHeaders" :key="h.key">
                                        <th scope="col" class="num" x-text="h.label"></th>
                                    </template>
                                    <th scope="col">Status</th>
                                    <th scope="col">Evaluation</th>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="r in rows" :key="r.no">
                                    <tr @click="openDetail(r.no)">
                                        <td><button type="button" class="row-btn" :id="'btn-percobaan-' + r.no" :aria-label="'Open details for trial ' + r.no">Trial <span x-text="r.noPad"></span></button></td>
                                        <td class="cell-date"><span style="color: inherit" x-text="r.tanggal"></span> <span x-text="'· ' + r.jam"></span></td>
                                        <template x-for="h in sensorHeaders" :key="h.key">
                                            <td class="num"><span class="cell" :class="r.cells[h.key].cls" x-text="r.cells[h.key].txt"></span></td>
                                        </template>
                                        <td style="white-space: nowrap">
                                            <template x-if="r.baik">
                                                <span class="tag tag-ok lg"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Good</span>
                                            </template>
                                            <template x-if="!r.baik">
                                                <span class="tag tag-bad lg"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 6v8"/><path d="M12 18h.01"/></svg>Poor</span>
                                            </template>
                                        </td>
                                        <td class="cell-eval" x-text="r.evaluasi"></td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                            <template x-if="rows.length === 0">
                                <div class="empty">
                                    <div class="empty-title">No matching trials</div>
                                    <div class="empty-text">Try another keyword or change the status filter.</div>
                                    <button type="button" class="btn-outline sm btn" @click="clearAll()">Clear search &amp; filters</button>
                                </div>
                            </template>
                        </div>
                        <p class="footnote">Cell legend: <span class="key bad">↑ / ↓ out of range</span> · <span class="key warn">~ near limit</span> · A trial is Poor when any parameter is outside its safe range.</p>
                    </section>
                </div>
            </template>

            {{-- ============ VIEW: EVALUATION (completed trial sessions) ============ --}}
            <template x-if="view === 'evaluasi'">
                <div class="view" :class="viewClass">
                    <section id="evaluasi-page" class="card eval" aria-label="Trial history by sample">
                        <div class="section-head">
                            <div>
                                <h2 class="section-title sm">Drinking Water Trial History</h2>
                                <p class="section-sub">Each completed trial is shown with its average sensor readings.</p>
                            </div>
                            <button type="button" class="btn-outline btn btn-refresh" :class="{ 'is-loading': historyLoading }" @click="refreshHistory()" :disabled="historyLoading" :aria-busy="historyLoading.toString()">
                                <svg class="refresh-icon" :class="{ spin: historyLoading }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                                <span x-text="historyLoading ? 'Refreshing…' : 'Refresh'">Refresh</span>
                            </button>
                        </div>

                        <template x-if="completedTrials.length === 0">
                            <div class="empty" style="border-top: none">
                                <div class="empty-title">No completed trials yet</div>
                                <div class="empty-text">Trials appear here after you use "End This Trial".</div>
                            </div>
                        </template>

                        <template x-if="completedTrials.length > 0">
                            <div class="table-wrap">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th scope="col">Trial Name</th>
                                        <th scope="col">Time Range</th>
                                        <th scope="col" class="num">Trial Count</th>
                                        <th scope="col" class="num">Avg. pH</th>
                                        <th scope="col" class="num">Avg. TDS (ppm)</th>
                                        <th scope="col" class="num">Avg. Turbidity (NTU)</th>
                                        <th scope="col" class="num">Avg. Temperature (°C)</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <template x-for="t in completedTrials" :key="t.id">
                                        <tr @click="openTrialDetail(t)">
                                            <td><button type="button" class="row-btn" x-text="t.name"></button></td>
                                            <td class="cell-date">
                                                <span style="color: inherit" x-text="t.started_at"></span>
                                                <span> → <span x-text="t.ended_at"></span></span>
                                            </td>
                                            <td class="num"><strong x-text="t.jumlah_percobaan"></strong></td>
                                            <td class="num"><span class="cell" :class="trialAvgClass('ph', t)" x-text="fmtAvg(t.rata_rata.ph, 1)"></span></td>
                                            <td class="num"><span class="cell" :class="trialAvgClass('tds', t)" x-text="fmtAvg(t.rata_rata.tds, 0)"></span></td>
                                            <td class="num"><span class="cell" :class="trialAvgClass('kekeruhan', t)" x-text="fmtAvg(t.rata_rata.kekeruhan, 1)"></span></td>
                                            <td class="num"><span class="cell" :class="trialAvgClass('suhu', t)" x-text="fmtAvg(t.rata_rata.suhu, 1)"></span></td>
                                            <td>
                                                <template x-if="trialStatusBaik(t)">
                                                    <span class="tag tag-ok lg"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Good</span>
                                                </template>
                                                <template x-if="!trialStatusBaik(t)">
                                                    <span class="tag tag-bad lg"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 6v8"/><path d="M12 18h.01"/></svg>Poor</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </section>
                </div>
            </template>
        </main>
    </div>
    </div>

    {{-- ============ TOAST ============ --}}
    <div class="toasts" aria-live="polite" aria-atomic="true">
        <template x-if="toast && toast.kind === 'success'">
            <div class="toast">
                <span class="toast-icon ok" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                <div class="toast-body"><div class="toast-title" x-text="toast.title"></div><div class="toast-msg" x-text="toast.msg"></div></div>
                <button type="button" class="toast-close" aria-label="Dismiss notification" @click="closeToast()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            </div>
        </template>
        <template x-if="toast && toast.kind === 'info'">
            <div class="toast">
                <span class="toast-icon info" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 3v8"/><path d="M6.3 6.3a8 8 0 1 0 11.4 0"/></svg></span>
                <div class="toast-body">
                    <div class="toast-title" x-text="toast.title"></div>
                    <div class="toast-msg" x-text="toast.msg"></div>
                    <button type="button" class="btn-primary xs btn" @click="togglePower()">Turn on again</button>
                </div>
                <button type="button" class="toast-close" aria-label="Dismiss notification" @click="closeToast()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            </div>
        </template>
        <template x-if="toast && toast.kind === 'error'">
            <div class="toast err">
                <span class="toast-icon err" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 6v8"/><path d="M12 18h.01"/></svg></span>
                <div class="toast-body">
                    <div class="toast-title" x-text="toast.title"></div>
                    <div class="toast-msg" x-text="toast.msg"></div>
                    <button type="button" class="btn-retry btn" @click="toastRetry()">Try again</button>
                </div>
                <button type="button" class="toast-close" aria-label="Dismiss notification" @click="closeToast()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            </div>
        </template>
    </div>

    {{-- ============ DIALOG DETAIL ============ --}}
    <template x-if="d">
        <div class="overlay" @keydown="dialogKey($event)" @click.self="closeDetail()">
            <div id="dialog-detail" class="dialog" role="dialog" aria-modal="true" aria-labelledby="judul-detail" tabindex="-1">
                <div class="dialog-head">
                    <div>
                        <div class="dialog-eyebrow"><span x-text="sampleLabel"></span> · <span x-text="d.tanggal"></span>, <span x-text="d.jam"></span> WIB</div>
                        <h2 id="judul-detail" class="dialog-title">Trial <span x-text="d.noPad"></span></h2>
                    </div>
                    <div class="dialog-head-right">
                        <template x-if="d.baik">
                            <span class="tag tag-ok xl"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>GOOD</span>
                        </template>
                        <template x-if="!d.baik">
                            <span class="tag tag-bad xl"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 6v8"/><path d="M12 18h.01"/></svg>POOR</span>
                        </template>
                        <button type="button" class="btn-close btn" aria-label="Close details" @click="closeDetail()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                    </div>
                </div>
                <div class="detail-grid">
                    <template x-for="x in d.rows" :key="x.key">
                        <div class="detail-item" :class="{ bad: x.lv === 'bad' }">
                            <div class="detail-item-head"><span class="detail-name" x-text="x.nama"></span><span class="detail-status" :class="x.lv" x-text="x.status"></span></div>
                            <div class="detail-val" x-text="x.val"></div>
                            <div class="detail-range">Normal <span x-text="x.range"></span></div>
                        </div>
                    </template>
                </div>
                <div class="detail-eval">
                    <div class="detail-eval-label">Automatic evaluation</div>
                    <div class="detail-eval-text" x-text="d.evaluasi"></div>
                </div>
                <div class="dialog-foot">
                    <div class="pager">
                        <button type="button" class="btn-outline btn" @click="stepDetail(-1)" :disabled="d.noPrev">‹ Previous</button>
                        <button type="button" class="btn-outline btn" @click="stepDetail(1)" :disabled="d.noNext">Next ›</button>
                    </div>
                    <button type="button" class="btn-primary sm btn" @click="closeDetail()">Close</button>
                </div>
            </div>
        </div>
    </template>

    {{-- ============ TRIAL DETAIL DIALOG ============ --}}
    <template x-if="trialDetail">
        <div class="overlay" @click.self="trialDetail = null">
            <div class="dialog" role="dialog" aria-modal="true" tabindex="-1" style="max-width: 900px">
                <div class="dialog-head">
                    <div>
                        <div class="dialog-eyebrow">Completed Trial</div>
                        <h2 class="dialog-title" x-text="trialDetail.name"></h2>
                    </div>
                    <div class="dialog-head-right">
                        <template x-if="trialStatusBaik(trialDetail)">
                            <span class="tag tag-ok xl">GOOD</span>
                        </template>
                        <template x-if="!trialStatusBaik(trialDetail)">
                            <span class="tag tag-bad xl">POOR</span>
                        </template>
                        <button type="button" class="btn-close btn" aria-label="Close" @click="trialDetail = null"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                    </div>
                </div>
                <div class="detail-eval" style="margin-top: 0">
                    <div class="detail-eval-label">Average Results</div>
                    <div class="detail-grid" style="padding: 12px 0">
                        <div class="detail-item" :class="{ bad: trialAvgClass('ph', trialDetail) === 'bad' }">
                            <div class="detail-item-head"><span class="detail-name">pH</span><span class="detail-status" :class="trialAvgClass('ph', trialDetail)" x-text="trialAvgClass('ph', trialDetail) === 'bad' ? 'Out of range' : 'Normal'"></span></div>
                            <div class="detail-val" x-text="fmtAvg(trialDetail.rata_rata.ph, 1)"></div>
                        </div>
                        <div class="detail-item" :class="{ bad: trialAvgClass('tds', trialDetail) === 'bad' }">
                            <div class="detail-item-head"><span class="detail-name">TDS</span><span class="detail-status" :class="trialAvgClass('tds', trialDetail)" x-text="trialAvgClass('tds', trialDetail) === 'bad' ? 'Out of range' : 'Normal'"></span></div>
                            <div class="detail-val"><span x-text="fmtAvg(trialDetail.rata_rata.tds, 0)"></span> ppm</div>
                        </div>
                        <div class="detail-item" :class="{ bad: trialAvgClass('kekeruhan', trialDetail) === 'bad' }">
                            <div class="detail-item-head"><span class="detail-name">Turbidity</span><span class="detail-status" :class="trialAvgClass('kekeruhan', trialDetail)" x-text="trialAvgClass('kekeruhan', trialDetail) === 'bad' ? 'Out of range' : 'Normal'"></span></div>
                            <div class="detail-val"><span x-text="fmtAvg(trialDetail.rata_rata.kekeruhan, 1)"></span> NTU</div>
                        </div>
                        <div class="detail-item" :class="{ bad: trialAvgClass('suhu', trialDetail) === 'bad' }">
                            <div class="detail-item-head"><span class="detail-name">Temperature</span><span class="detail-status" :class="trialAvgClass('suhu', trialDetail)" x-text="trialAvgClass('suhu', trialDetail) === 'bad' ? 'Out of range' : 'Normal'"></span></div>
                            <div class="detail-val"><span x-text="fmtAvg(trialDetail.rata_rata.suhu, 1)"></span> °C</div>
                        </div>
                    </div>
                </div>
                <div style="padding: 0 26px 12px">
                    <div class="stat-label" style="margin-bottom: 4px">Trial count: <strong x-text="trialDetail.jumlah_percobaan"></strong></div>
                    <div class="stat-label">Time range: <span x-text="trialDetail.started_at"></span> → <span x-text="trialDetail.ended_at"></span></div>
                </div>

                {{-- Trials recorded in this session --}}
                <template x-if="trialDetail.percobaan && trialDetail.percobaan.length > 0">
                    <div style="padding: 0 26px 16px">
                        <div class="detail-eval-label" style="margin-bottom: 10px">Trial History</div>
                        <div class="table-wrap" style="max-height: 320px; overflow-y: auto">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">No.</th>
                                    <th scope="col">Date &amp; Time</th>
                                    <th scope="col" class="num">pH</th>
                                    <th scope="col" class="num">TDS (ppm)</th>
                                    <th scope="col" class="num">Turbidity (NTU)</th>
                                    <th scope="col" class="num">Temperature (°C)</th>
                                    <th scope="col">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="p in trialPercobaanRows(trialDetail)" :key="p.no">
                                    <tr>
                                        <td x-text="'T' + p.noPad"></td>
                                        <td class="cell-date"><span style="color: inherit" x-text="p.tanggal"></span> <span x-text="'· ' + p.jam"></span></td>
                                        <td class="num"><span class="cell" :class="p.cells.ph.cls" x-text="p.cells.ph.txt"></span></td>
                                        <td class="num"><span class="cell" :class="p.cells.tds.cls" x-text="p.cells.tds.txt"></span></td>
                                        <td class="num"><span class="cell" :class="p.cells.kekeruhan.cls" x-text="p.cells.kekeruhan.txt"></span></td>
                                        <td class="num"><span class="cell" :class="p.cells.suhu.cls" x-text="p.cells.suhu.txt"></span></td>
                                        <td style="white-space: nowrap">
                                            <template x-if="p.baik">
                                                <span class="tag tag-ok lg"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Good</span>
                                            </template>
                                            <template x-if="!p.baik">
                                                <span class="tag tag-bad lg"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 6v8"/><path d="M12 18h.01"/></svg>Poor</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <div class="dialog-foot">
                    <div></div>
                    <button type="button" class="btn-primary sm btn" @click="trialDetail = null">Close</button>
                </div>
            </div>
        </div>
    </template>
</div>
</body>
</html>
