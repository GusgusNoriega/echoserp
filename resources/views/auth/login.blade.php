<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-mode="light" data-accent="aurora">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f4f7fb">
        <title>Iniciar sesion | {{ config('admin.brand.name', config('app.name', 'Laravel')) }}</title>

        @php
            $palettes = config('admin.palettes', []);
            $paletteKeys = collect($palettes)->pluck('key')->values();
            $brand = config('admin.brand', []);
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
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="auth-shell">
            <section class="auth-panel auth-panel--brand">
                <div class="auth-panel__copy">
                    <x-admin.brand-lockup :brand="$brand" variant="auth" :show-copy="false" />
                    <p class="eyebrow">Acceso protegido</p>
                    <p class="auth-intro">
                        Inicia sesion para entrar al panel administrativo y mantener las vistas internas fuera del acceso publico.
                    </p>
                </div>

                <div class="auth-highlight-grid">
                    <article class="auth-highlight">
                        <strong>Sesion web</strong>
                        <span>Laravel controla acceso, regeneracion de sesion y cierre seguro.</span>
                    </article>
                    <article class="auth-highlight">
                        <strong>Panel privado</strong>
                        <span>Todo <code>/admin</code> queda oculto hasta que exista una sesion valida.</span>
                    </article>
                    <article class="auth-highlight">
                        <strong>Base lista</strong>
                        <span>Puedes crecer despues a roles y permisos sin rehacer el flujo.</span>
                    </article>
                    <article class="auth-highlight">
                        <strong>Diseno actual</strong>
                        <span>Se mantiene la misma identidad visual del panel para no partir la experiencia.</span>
                    </article>
                </div>

                @if (app()->isLocal())
                    <div class="auth-dev-note">
                        <strong>Entorno de desarrollo</strong>
                        <p>Si ya corriste el seeder base, puedes probar con <code>admin@echoserp.test</code> y la clave <code>password</code>.</p>
                    </div>
                @endif
            </section>

            <section class="auth-panel auth-panel--form">
                <div class="auth-card-head">
                    <p class="eyebrow">Inicio de sesion</p>
                    <h2>Entrar al panel</h2>
                    <p class="section-copy">Usa tu correo y contrasena para acceder al area administrativa.</p>
                </div>

                @if (session()->has('status'))
                    <section class="feedback-banner feedback-banner--success" role="status">
                        <strong>Operacion completada.</strong>
                        <p>{{ session('status') }}</p>
                    </section>
                @endif

                @if ($errors->any())
                    <section class="feedback-banner feedback-banner--danger" role="alert">
                        <strong>No se pudo iniciar sesion.</strong>
                        <p>{{ $errors->first() }}</p>
                    </section>
                @endif

                <form class="auth-form" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <label class="form-field">
                        <span>Correo</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus
                        >

                        @error('email')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="form-field">
                        <span>Contrasena</span>
                        <input
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >

                        @error('password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="toggle-field auth-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Mantener sesion abierta en este equipo</span>
                    </label>

                    <button class="button-link button-link--primary button-link--full" type="submit">
                        Iniciar sesion
                    </button>
                </form>

                <div class="auth-toolbar">
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
            </section>
        </main>
    </body>
</html>
