<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            body {
                background-color: #0f1117 !important;
                background-image:
                    linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px) !important;
                background-size: 44px 44px !important;
                position: relative;
            }
            body::before {
                content: '';
                position: fixed;
                top: -20%;
                left: 50%;
                transform: translateX(-50%);
                width: 800px;
                height: 600px;
                background: radial-gradient(ellipse at center, rgba(163,230,53,.07) 0%, transparent 65%);
                pointer-events: none;
                z-index: 0;
            }
            .auth-card {
                background: rgba(24,24,27,.85) !important;
                border: 1px solid rgba(255,255,255,.07) !important;
                border-radius: 1rem !important;
                backdrop-filter: blur(12px);
                box-shadow: 0 25px 50px rgba(0,0,0,.5) !important;
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6">

            {{-- Brand --}}
            <a href="{{ route('home') }}" class="mb-7 flex flex-col items-center gap-3" wire:navigate>
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl"
                      style="background: linear-gradient(135deg, #84cc16, #a3e635); box-shadow: 0 0 32px rgba(163,230,53,.3);">
                    <x-app-logo-icon class="size-8 text-zinc-900" />
                </span>
                <div class="text-center">
                    <div class="text-base font-bold leading-tight tracking-tight" style="color:#a3e635;">
                        {{ config('app.name') }}
                    </div>
                    <div class="mt-0.5 text-[10px] font-medium uppercase tracking-widest text-zinc-600">
                        AMMS Platform
                    </div>
                </div>
            </a>

            {{-- Card --}}
            <div class="auth-card w-full max-w-sm px-8 py-8">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <p class="mt-7 text-[11px] text-zinc-700">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
