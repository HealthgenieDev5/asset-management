<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AMMS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }

        body {
            min-height: 100vh;
            background: #0f1117;
            color: #e4e4e7;
            display: flex;
            flex-direction: column;
        }

        /* Subtle grid */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 44px 44px;
            pointer-events: none;
            z-index: 0;
        }

        /* Glow */
        body::after {
            content: '';
            position: fixed;
            top: -15%;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 600px;
            background: radial-gradient(ellipse at center, rgba(163,230,53,.08) 0%, transparent 65%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── Nav ── */
        nav {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,.05);
            backdrop-filter: blur(10px);
            background: rgba(15,17,23,.75);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: .625rem;
            text-decoration: none;
        }

        .brand-icon {
            width: 2rem;
            height: 2rem;
            border-radius: .5rem;
            background: linear-gradient(135deg, #84cc16, #a3e635);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 0 16px rgba(163,230,53,.25);
        }
        .brand-icon svg { width: 1rem; height: 1rem; color: #fff; }

        .brand-name {
            font-size: .8rem;
            font-weight: 700;
            color: #a3e635;
            letter-spacing: -.01em;
            line-height: 1.2;
        }
        .brand-abbr {
            font-size: .6rem;
            font-weight: 500;
            color: #52525b;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .nav-actions { display: flex; align-items: center; gap: .5rem; }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            padding: .4rem 1rem;
            border-radius: .375rem;
            font-size: .8rem;
            font-weight: 500;
            color: #a1a1aa;
            text-decoration: none;
            transition: color .15s, background .15s;
        }
        .btn-ghost:hover { color: #f4f4f5; background: rgba(255,255,255,.06); }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .4rem 1.1rem;
            border-radius: .375rem;
            font-size: .8rem;
            font-weight: 600;
            color: #0f1117;
            text-decoration: none;
            background: linear-gradient(135deg, #84cc16, #a3e635);
            border: 1px solid rgba(163,230,53,.4);
            transition: opacity .15s, transform .1s;
        }
        .btn-primary:hover { opacity: .88; transform: translateY(-1px); }

        /* ── Hero ── */
        .hero {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 5rem 1.5rem 3.5rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .25rem .875rem;
            border-radius: 9999px;
            border: 1px solid rgba(163,230,53,.25);
            background: rgba(163,230,53,.07);
            font-size: .68rem;
            font-weight: 600;
            color: #a3e635;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 1.75rem;
        }
        .badge-dot {
            width: .35rem;
            height: .35rem;
            border-radius: 50%;
            background: #a3e635;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .4; transform: scale(.75); }
        }

        .hero-title {
            font-size: clamp(1.9rem, 5vw, 3.4rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.03em;
            color: #f4f4f5;
            margin-bottom: 1.25rem;
            max-width: 680px;
        }
        .hero-title span {
            background: linear-gradient(130deg, #84cc16 0%, #a3e635 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: .95rem;
            color: #71717a;
            max-width: 460px;
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .65rem 1.75rem;
            border-radius: .5rem;
            font-size: .875rem;
            font-weight: 600;
            color: #0f1117;
            text-decoration: none;
            background: linear-gradient(135deg, #84cc16, #a3e635);
            border: 1px solid rgba(163,230,53,.35);
            box-shadow: 0 0 28px rgba(163,230,53,.18), 0 4px 14px rgba(0,0,0,.3);
            transition: all .2s;
        }
        .btn-cta:hover {
            box-shadow: 0 0 40px rgba(163,230,53,.28), 0 8px 22px rgba(0,0,0,.4);
            transform: translateY(-2px);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .65rem 1.5rem;
            border-radius: .5rem;
            font-size: .875rem;
            font-weight: 500;
            color: #a1a1aa;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.09);
            background: rgba(255,255,255,.03);
            transition: all .2s;
        }
        .btn-outline:hover {
            color: #f4f4f5;
            border-color: rgba(255,255,255,.16);
            background: rgba(255,255,255,.07);
        }

        /* ── Stats ── */
        .stats {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            padding: 0 1.5rem 4rem;
            gap: 0;
        }
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.125rem 2.25rem;
            border-right: 1px solid rgba(255,255,255,.05);
        }
        .stat:last-child { border-right: none; }
        .stat-val {
            font-size: 1.375rem;
            font-weight: 700;
            color: #f4f4f5;
            letter-spacing: -.02em;
        }
        .stat-lbl {
            font-size: .68rem;
            color: #52525b;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-top: .2rem;
            font-weight: 500;
        }

        /* ── Features ── */
        .features-wrap {
            position: relative;
            z-index: 1;
            max-width: 960px;
            margin: 0 auto 4.5rem;
            width: 100%;
            padding: 0 1.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1px;
            background: rgba(255,255,255,.055);
            border: 1px solid rgba(255,255,255,.055);
            border-radius: .875rem;
            overflow: hidden;
        }

        .feature {
            background: #0f1117;
            padding: 1.625rem;
            transition: background .2s;
        }
        .feature:hover { background: #131520; }

        .feat-icon {
            width: 2.125rem;
            height: 2.125rem;
            border-radius: .5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .875rem;
        }
        .feat-icon svg { width: 1.0625rem; height: 1.0625rem; }

        .feat-title {
            font-size: .78rem;
            font-weight: 600;
            color: #e4e4e7;
            margin-bottom: .3rem;
        }
        .feat-desc {
            font-size: .73rem;
            color: #52525b;
            line-height: 1.65;
        }

        /* ── Footer ── */
        footer {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 1.25rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,.04);
            font-size: .7rem;
            color: #3f3f46;
        }

        @media (max-width: 640px) {
            nav { padding: .875rem 1.125rem; }
            .stat { padding: .875rem 1.25rem; border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
            .stat:last-child { border-bottom: none; }
            .features-grid { border-radius: 0; }
            .features-wrap { padding: 0; }
        }
    </style>
</head>
<body>

    {{-- Nav --}}
    <nav>
        <a href="/" class="nav-brand">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                </svg>
            </div>
            <div>
                <div class="brand-name">{{ config('app.name') }}</div>
                <div class="brand-abbr">AMMS Platform</div>
            </div>
        </a>

        @if (Route::has('login'))
            <div class="nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-primary">Get started</a>
                    @endif
                @endauth
            </div>
        @endif
    </nav>

    {{-- Hero --}}
    <section class="hero">
        <div class="badge">
            <span class="badge-dot"></span>
            Enterprise Asset Management
        </div>

        <h1 class="hero-title">
            Track, Manage &amp; Maintain<br>
            <span>Every Asset. Effortlessly.</span>
        </h1>

        <p class="hero-sub">
            A unified platform to register, monitor, and service all your company assets — from warranty tracking to AMC contracts, insurance, and automated reminders.
        </p>

        <div class="hero-cta">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-cta">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    Go to Dashboard
                </a>
                <a href="{{ route('assets.index') }}" class="btn-outline">
                    Asset Register
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-cta">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Sign In to Continue
                </a>
                <a href="#features" class="btn-outline">
                    Explore features
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </a>
            @endauth
        </div>
    </section>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat">
            <div class="stat-val">360°</div>
            <div class="stat-lbl">Asset Visibility</div>
        </div>
        <div class="stat">
            <div class="stat-val">Real-time</div>
            <div class="stat-lbl">Expiry Alerts</div>
        </div>
        <div class="stat">
            <div class="stat-val">Multi-type</div>
            <div class="stat-lbl">Document Storage</div>
        </div>
        <div class="stat">
            <div class="stat-val">CSV / Excel</div>
            <div class="stat-lbl">Instant Export</div>
        </div>
    </div>

    {{-- Features --}}
    <div id="features" class="features-wrap">
        <div class="features-grid">

            <div class="feature">
                <div class="feat-icon" style="background:rgba(163,230,53,.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#a3e635">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                </div>
                <div class="feat-title">Asset Register</div>
                <div class="feat-desc">Centralised registry for all fixed assets with category, location, custodian, and full lifecycle tracking.</div>
            </div>

            <div class="feature">
                <div class="feat-icon" style="background:rgba(16,185,129,.09);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#34d399">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div class="feat-title">Warranty Tracking</div>
                <div class="feat-desc">Monitor original and extended warranty periods with automated alerts before they expire.</div>
            </div>

            <div class="feature">
                <div class="feat-icon" style="background:rgba(245,158,11,.09);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#fbbf24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.24-1.793.95-.95a3.038 3.038 0 0 0 0-4.302l-1.218-1.218a3.037 3.037 0 0 0-4.302 0l-.95.95" />
                    </svg>
                </div>
                <div class="feat-title">Service & AMC</div>
                <div class="feat-desc">Log service history, parts used, and manage Annual Maintenance Contract dates and renewals.</div>
            </div>

            <div class="feature">
                <div class="feat-icon" style="background:rgba(59,130,246,.09);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#60a5fa">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="feat-title">Smart Reminders</div>
                <div class="feat-desc">Automated reminders for expiring warranties, insurance, AMC contracts, and vehicle compliance.</div>
            </div>

            <div class="feature">
                <div class="feat-icon" style="background:rgba(163,230,53,.09);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#a3e635">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                    </svg>
                </div>
                <div class="feat-title">Document Storage</div>
                <div class="feat-desc">Attach invoices, warranty cards, insurance policies, and certificates directly to each asset.</div>
            </div>

            <div class="feature">
                <div class="feat-icon" style="background:rgba(20,184,166,.09);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#2dd4bf">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div class="feat-title">Reports & Export</div>
                <div class="feat-desc">Generate detailed reports by category, status, or expiry type and export to Excel instantly.</div>
            </div>

        </div>
    </div>

    <footer>
        &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; All rights reserved.
    </footer>

</body>
</html>
