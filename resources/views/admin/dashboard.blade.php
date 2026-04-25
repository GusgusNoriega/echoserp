@extends('layouts.admin')

@section('content')
    @includeWhen(! $isReady, 'admin.partials.access-warning')

    <section class="hero-grid">
        <article class="hero-card">
            <p class="section-kicker">Layout maestro</p>
            <h2>Un panel administrativo listo para crecer con usuarios, roles y permisos sin rehacer su base visual.</h2>
            <p class="section-copy">
                El panel ahora tiene una estructura RBAC simple y extensible para que puedas sumar nuevas vistas,
                controlar acceso por modulo y mantener la navegacion centralizada.
            </p>

            <div class="hero-actions">
                <a class="button-link button-link--primary" href="{{ route('admin.users.index') }}">
                    Abrir usuarios
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.permissions.index') }}">
                    Revisar permisos
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Sidebar acordeon</span>
                <span class="pill">RBAC base</span>
                <span class="pill">Responsive real</span>
                <span class="pill">Facil de ampliar</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Estado actual</p>
                    <h3>Resumen del modulo administrativo</h3>
                </div>
            </div>

            <ul class="signal-list">
                @foreach ($highlights as $highlight)
                    <li>
                        <span>{{ $highlight['label'] }}</span>
                        <strong>{{ $highlight['value'] }}</strong>
                    </li>
                @endforeach
            </ul>
        </aside>
    </section>

    <section class="metrics-grid">
        @foreach ($stats as $stat)
            <article class="metric-card">
                <strong>{{ $stat['value'] }}</strong>
                <span>{{ $stat['label'] }}</span>
                <small>{{ $stat['detail'] }}</small>
            </article>
        @endforeach
    </section>

    <section class="content-grid">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Modulos sugeridos</p>
                    <h3>Puntos de entrada para seguir construyendo</h3>
                </div>
            </div>

            <div class="module-grid">
                @foreach ($modules as $module)
                    <a class="module-card" href="{{ route($module['route']) }}">
                        <strong>{{ $module['title'] }}</strong>
                        <span>{{ $module['description'] }}</span>
                    </a>
                @endforeach
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Crecimiento recomendado</p>
                    <h3>Como escalar este panel sin romper la base</h3>
                </div>
            </div>

            <ol class="roadmap-list">
                @foreach ($roadmap as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>
        </article>
    </section>
@endsection
