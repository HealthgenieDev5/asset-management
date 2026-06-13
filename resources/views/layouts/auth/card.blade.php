<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            body {
                background: #0f1117;
                background-image:
                    linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
                background-size: 44px 44px;
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
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6">

            {{-- Logo + Brand --}}
            <a href="{{ route('home') }}" class="mb-8 flex flex-col items-center gap-3" wire:navigate>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl"
                      style="background: linear-gradient(135deg, #84cc16, #a3e635); box-shadow: 0 0 20px rgba(163,230,53,.25);">
                    <x-app-logo-icon class="size-6 fill-current text-zinc-900" />
                </span>
                <div class="text-center">
                    <div class="text-sm font-bold text-[#a3e635] leading-tight tracking-tight">
                        {{ config('app.name') }}
                    </div>
                    <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-600 mt-0.5">AMMS Platform</div>
                </div>
            </a>

            {{-- Card --}}
            <div class="w-full max-w-sm rounded-2xl border border-white/[0.06] bg-zinc-900/80 shadow-2xl backdrop-blur-sm">
                <div class="px-8 py-8">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            <p class="mt-8 text-[11px] text-zinc-700">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
