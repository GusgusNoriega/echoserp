<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-mode="light" data-accent="aurora" data-sidebar="closed">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f4f7fb">
        <title>{{ ($pageTitle ?? 'Panel administrativo') . ' | ' . config('app.name', 'Laravel') }}</title>

        @php
            $palettes = config('admin.palettes', []);
            $paletteKeys = collect($palettes)->pluck('key')->values();
        @endphp

        <script>
            (() => {
                const root = document.documentElement;
                const paletteKeys = @json($paletteKeys);
                const storedMode = (() => {
                    try {
                        return window.localStorage.getItem('echoserp:mode');
                    } catch (error) {
                        return null;
                    }
                })();
                const storedAccent = (() => {
                    try {
                        return window.localStorage.getItem('echoserp:accent');
                    } catch (error) {
                        return null;
                    }
                })();
                const fallbackMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

                root.dataset.mode = ['light', 'dark'].includes(storedMode) ? storedMode : fallbackMode;
                root.dataset.accent = paletteKeys.includes(storedAccent) ? storedAccent : 'aurora';
                root.dataset.sidebar = 'closed';
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-initial-modal="{{ session('modal', '') }}">
        @php
            $brand = config('admin.brand', []);
            $navigation = config('admin.navigation', []);
            $currentUser = auth()->user();
        @endphp

        <div class="admin-shell">
            <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar menu lateral"></button>

            <x-admin.sidebar :navigation="$navigation" :brand="$brand" />

            <div class="admin-main">
                <header class="admin-topbar">
                    <div class="topbar-heading">
                        <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu lateral">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
                            </svg>
                        </button>

                        <div class="topbar-copy">
                            <p class="eyebrow">{{ $eyebrow ?? 'Panel administrativo' }}</p>
                            <h1>{{ $pageTitle ?? 'Dashboard' }}</h1>

                            @if (! empty($pageDescription))
                                <p class="page-description">{{ $pageDescription }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="topbar-controls">
                        @if ($currentUser)
                            <div class="topbar-user">
                                <div class="topbar-user__identity">
                                    <span>Sesion activa</span>
                                    <strong>{{ $currentUser->name }}</strong>
                                    <small>{{ $currentUser->email }}</small>
                                </div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <button class="button-link button-link--ghost button-link--compact" type="submit">
                                        Cerrar sesion
                                    </button>
                                </form>
                            </div>
                        @endif

                        <div class="mode-switch" role="group" aria-label="Cambiar modo del panel">
                            <button class="mode-button" type="button" data-set-mode="light" aria-pressed="false">
                                Claro
                            </button>
                            <button class="mode-button" type="button" data-set-mode="dark" aria-pressed="false">
                                Oscuro
                            </button>
                        </div>

                        <div class="palette-switch" role="group" aria-label="Cambiar paleta del panel">
                            @foreach ($palettes as $palette)
                                <button
                                    class="palette-button"
                                    type="button"
                                    data-set-accent="{{ $palette['key'] }}"
                                    data-theme-color="{{ $palette['color'] }}"
                                    aria-pressed="false"
                                    aria-label="Usar paleta {{ $palette['label'] }}"
                                >
                                    <span class="palette-dot" style="--palette-color: {{ $palette['color'] }}"></span>
                                    <span>{{ $palette['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </header>

                <main class="admin-content">
                    @if (session()->has('status'))
                        <section class="feedback-banner feedback-banner--success" role="status">
                            <strong>Operacion completada.</strong>
                            <p>{{ session('status') }}</p>
                        </section>
                    @endif

                    @if (session()->has('error'))
                        <section class="feedback-banner feedback-banner--danger" role="alert">
                            <strong>Hay algo pendiente.</strong>
                            <p>{{ session('error') }}</p>
                        </section>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
