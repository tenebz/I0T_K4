@php
    $code = trim($__env->yieldContent('code'));
    $digits = str_split(str_pad($code, 3, ' '));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · @yield('title') — AquaSense</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Rubik+Bubbles&display=swap">
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}?v={{ @filemtime(public_path('css/errors.css')) }}">
    <script defer src="{{ asset('js/errors.js') }}?v={{ @filemtime(public_path('js/errors.js')) }}"></script>
</head>
<body class="err-{{ $code }}" data-home="{{ url('/') }}">

{{-- Gradien ubur-ubur, dipakai bersama oleh semua ubur-ubur di errors.js --}}
<svg width="0" height="0" style="position: absolute" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="jelly-grad" x1="12" y1="7" x2="56" y2="37" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#FBCFE8"/>
            <stop offset="50%" stop-color="#F472B6"/>
            <stop offset="100%" stop-color="#DB2777"/>
        </linearGradient>
    </defs>
</svg>

<div class="layer sun-rays" aria-hidden="true"></div>
<div class="layer bubbles" id="bubbles" aria-hidden="true"></div>
<div class="layer swarm" id="swarm" aria-hidden="true"></div>

<header class="top">
    <div class="top-left">
        <a class="pill brand" href="{{ url('/') }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M2.5 9c2.5 0 2.5 4.2 5 4.2S10 9 12 9s2.5 4.2 5 4.2S19.5 9 21.5 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M2.5 15c2.5 0 2.5 4.2 5 4.2S10 15 12 15s2.5 4.2 5 4.2S19.5 15 21.5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
            </svg>
            AquaSense
        </a>
        <span class="pill zone"><span class="ping" aria-hidden="true"></span>@yield('zone')</span>
    </div>
    <button type="button" class="pill jelly-counter" data-action="summon" aria-label="Summon a jellyfish. Jellyfish tapped: 0" title="Summon a jellyfish">
        <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M6 16C6 9.5 10.5 5 16 5C21.5 5 26 9.5 26 16C26 18 22 19 16 19C10 19 6 18 6 16Z" fill="#FBCFE8" stroke="#FFFFFF" stroke-width="2"/>
            <path d="M11 19V27M16 19V28M21 19V27" stroke="#FBCFE8" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span class="jelly-label">Jellyfish tapped</span>
        <span class="jelly-count" id="jelly-count" aria-hidden="true">0</span>
    </button>
</header>

<main class="main">
    <div class="code-wrap">
        <h1 class="code" aria-label="Error {{ $code }}">{{ $digits[0] }}<span class="mid">{{ $digits[1] }}</span>{{ $digits[2] }}</h1>
        <span class="deco-bubble b1" aria-hidden="true"></span>
        <span class="deco-bubble b2" aria-hidden="true"></span>
    </div>

    <section class="card" aria-labelledby="err-heading">
        <h2 id="err-heading">@yield('heading')</h2>
        <p>@yield('message')</p>
        <div class="actions">@yield('actions')</div>
    </section>

    <p class="tip">Tip: tap a pink jellyfish as it drifts by to make it glow.</p>
</main>

{{-- Dasar laut: pasir, mercusuar, jangkar, alat sensor AquaSense yang tenggelam, karang, dan rumput laut --}}
<div class="seabed" aria-hidden="true">
    <svg viewBox="0 0 1440 320" preserveAspectRatio="none">
        <defs>
            <linearGradient id="sand-back" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#FDE68A"/><stop offset="100%" stop-color="#F59E0B"/>
            </linearGradient>
            <linearGradient id="sand-front" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#D97706"/>
            </linearGradient>
            <linearGradient id="coral-rock" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#818CF8"/><stop offset="55%" stop-color="#4F46E5"/><stop offset="100%" stop-color="#1E1B4B"/>
            </linearGradient>
            <linearGradient id="coral-rock-dark" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#4338CA"/><stop offset="100%" stop-color="#0F172A"/>
            </linearGradient>
        </defs>

        {{-- Siluet karang jauh di horizon --}}
        <g opacity="0.55" fill="#7C3AED">
            <circle cx="180" cy="185" r="24"/><circle cx="215" cy="190" r="18"/>
            <rect x="640" y="168" width="14" height="27" rx="7"/><rect x="600" y="160" width="18" height="35" rx="9"/>
            <circle cx="1220" cy="182" r="28"/><circle cx="1260" cy="188" r="20"/>
        </g>

        <path d="M0,195 Q320,155 720,185 T1440,175 L1440,320 L0,320 Z" fill="url(#sand-back)"/>
        <path d="M710,190 Q660,210 730,225 T690,265" fill="none" stroke="#B45309" stroke-width="5" stroke-linecap="round" opacity="0.35"/>

        {{-- Jangkar tua --}}
        <g transform="translate(530, 118) scale(0.75)" opacity="0.8">
            <path d="M40 15 V75 M20 32 H60 M15 62 C15 82, 65 82, 65 62" stroke="#475569" stroke-width="8" stroke-linecap="round" fill="none"/>
            <circle cx="40" cy="12" r="7" stroke="#475569" stroke-width="5" fill="none"/>
            <polygon points="10,62 22,55 20,70" fill="#475569"/><polygon points="70,62 58,55 60,70" fill="#475569"/>
        </g>

        {{-- Mercusuar --}}
        <g transform="translate(705, 92) scale(0.85)">
            <polygon class="lamp-beam" points="40,8 -150,-40 -150,40"/>
            <polygon points="15,115 24,25 56,25 65,115" fill="#F8FAFC" stroke="#94A3B8" stroke-width="2"/>
            <polygon points="22,42 58,42 60,58 20,58" fill="#DC2626"/>
            <polygon points="18,75 62,75 64,92 16,92" fill="#DC2626"/>
            <rect x="21" y="14" width="38" height="12" rx="3" fill="#334155"/>
            <rect x="29" y="16" width="22" height="8" rx="2" fill="#FEF08A"/>
            <path d="M20 14 Q40 -2 60 14 Z" fill="#DC2626"/>
        </g>

        {{-- Alat sensor AquaSense yang tertancap di pasir; LED-nya mengikuti kode error --}}
        <g transform="translate(870, 98)">
            <rect x="46" y="70" width="8" height="92" rx="3" fill="#64748B"/>
            <path d="M50 30 V8" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"/>
            <circle cx="50" cy="6" r="4" fill="#94A3B8"/>
            <rect x="22" y="28" width="56" height="46" rx="10" fill="#F1F5F9" stroke="#475569" stroke-width="3"/>
            <rect x="30" y="37" width="40" height="18" rx="4" fill="#0F172A"/>
            <path d="M34 47 q4 -6 8 0 t8 0 t8 0 t8 0" stroke="#38BDF8" stroke-width="2" fill="none" stroke-linecap="round"/>
            <circle class="probe-led" cx="38" cy="64" r="4"/>
            <rect x="48" y="61" width="22" height="6" rx="3" fill="#CBD5E1"/>
            <path d="M44 162 Q50 150 56 162" fill="#B45309" opacity="0.4"/>
        </g>

        {{-- Lengkung karang oranye --}}
        <g transform="translate(965, 98) scale(0.9)" opacity="0.9">
            <path d="M20 120 C5 50, 45 10, 65 85 C85 5, 130 45, 110 120" stroke="#EA580C" stroke-width="18" stroke-linecap="round" fill="none"/>
            <path d="M20 120 C5 50, 45 10, 65 85 C85 5, 130 45, 110 120" stroke="#FB923C" stroke-width="7" stroke-linecap="round" fill="none"/>
        </g>

        <path d="M0,235 Q420,195 850,230 T1440,210 L1440,320 L0,320 Z" fill="url(#sand-front)"/>

        {{-- Pelampung bertuliskan kode error --}}
        <g transform="translate(115, 115)">
            <rect x="44" y="75" width="10" height="85" rx="4" fill="#92400E" transform="rotate(-8 48 110)"/>
            <g transform="rotate(-10 50 55)">
                <circle cx="50" cy="55" r="46" fill="#F97316" stroke="#C2410C" stroke-width="4"/>
                <path d="M12 36 L26 44 M74 44 L88 36 M12 74 L26 66 M74 66 L88 74" stroke="#FFFBEB" stroke-width="11"/>
                <circle cx="50" cy="55" r="23" fill="#38BDF8" stroke="#C2410C" stroke-width="4"/>
                <text x="50" y="62" text-anchor="middle" fill="#FFFFFF" font-size="19" font-weight="800" font-family="Fredoka, sans-serif">{{ $code }}</text>
                <text x="50" y="24" text-anchor="middle" fill="#1E293B" font-size="10.5" font-weight="800" font-family="Fredoka, sans-serif">AQUA</text>
                <text x="50" y="93" text-anchor="middle" fill="#1E293B" font-size="10.5" font-weight="800" font-family="Fredoka, sans-serif">SENSE</text>
            </g>
        </g>

        {{-- Karang & rumput laut kanan depan --}}
        <g transform="translate(1050, 145)">
            <path d="M35 125 V75 M35 95 L15 75 M35 85 L55 65 M15 75 V58 M55 65 V48" stroke="#EF4444" stroke-width="9" stroke-linecap="round" fill="none"/>
            <path class="kelp" d="M75 135 Q60 95 78 60 T72 5" stroke="#15803D" stroke-width="11" stroke-linecap="round" fill="none"/>
            <path class="kelp alt" d="M95 135 Q110 90 92 55 T98 15" stroke="#22C55E" stroke-width="9" stroke-linecap="round" fill="none"/>
            <path d="M135 125 V70 M135 92 L112 72 M135 82 L158 62 M112 72 L105 55 M158 62 L166 45" stroke="#06B6D4" stroke-width="9" stroke-linecap="round" fill="none"/>
        </g>

        {{-- Batu karang gelap kiri depan --}}
        <g>
            <path d="M-20,320 L-20,225 Q90,195 210,235 Q310,265 410,320 Z" fill="url(#coral-rock-dark)"/>
            <circle cx="65" cy="255" r="2.5" fill="#C7D2FE" opacity="0.8"/>
            <circle cx="110" cy="240" r="2" fill="#FBCFE8" opacity="0.8"/>
            <circle cx="150" cy="270" r="3" fill="#C7D2FE" opacity="0.7"/>
            <circle cx="220" cy="260" r="2" fill="#FFFFFF" opacity="0.8"/>
            <circle cx="280" cy="285" r="2.5" fill="#C7D2FE" opacity="0.7"/>
            <path class="kelp" d="M300 300 Q290 270 305 245 T298 205" stroke="#16A34A" stroke-width="8" stroke-linecap="round" fill="none"/>
        </g>

        {{-- Batu karang ungu kanan depan dengan polip --}}
        <g transform="translate(1130, 175)">
            <path d="M20,145 Q15,75 75,65 Q115,20 175,35 Q235,10 295,60 L320,145 Z" fill="url(#coral-rock)"/>
            <g transform="translate(95, 85)" fill="#FCE7F3">
                <circle cx="0" cy="-7" r="5"/><circle cx="7" cy="-2" r="5"/><circle cx="4" cy="6" r="5"/><circle cx="-4" cy="6" r="5"/><circle cx="-7" cy="-2" r="5"/>
                <circle cx="0" cy="0" r="4" fill="#F472B6"/>
            </g>
            <g transform="translate(185, 62) scale(1.15)" fill="#FFFFFF">
                <circle cx="0" cy="-7" r="5"/><circle cx="7" cy="-2" r="5"/><circle cx="4" cy="6" r="5"/><circle cx="-4" cy="6" r="5"/><circle cx="-7" cy="-2" r="5"/>
                <circle cx="0" cy="0" r="4" fill="#F59E0B"/>
            </g>
            <g transform="translate(30, 110)">
                <rect x="0" y="8" width="12" height="18" rx="6" fill="#FBCFE8"/><circle cx="6" cy="8" r="7" fill="#F472B6"/>
                <rect x="16" y="12" width="11" height="15" rx="5" fill="#FCE7F3"/><circle cx="21.5" cy="12" r="6" fill="#EC4899"/>
            </g>
        </g>
    </svg>
</div>

<div class="toast" id="toast" role="status" aria-live="polite">
    <span class="toast-icon" aria-hidden="true">🫧</span>
    <span id="toast-text"></span>
</div>

</body>
</html>
