@extends('layouts.admin')

@section('content')
    <section class="hero-grid hero-grid--single">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">{{ $eyebrow }}</p>
            <h2>{{ $pageTitle }}</h2>
            <p class="section-copy">{{ $summary }}</p>

            <div class="hero-actions">
                <a class="button-link button-link--primary" href="{{ route('admin.dashboard') }}">
                    Volver al dashboard
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.settings.appearance') }}">
                    Ajustar tema
                </a>
            </div>
        </article>
    </section>

    <section class="metrics-grid metrics-grid--three">
        @foreach ($metrics as $metric)
            <article class="metric-card">
                <strong>{{ $metric['value'] }}</strong>
                <span>{{ $metric['label'] }}</span>
                <small>{{ $metric['detail'] }}</small>
            </article>
        @endforeach
    </section>

    <section class="content-grid">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Siguiente implementacion</p>
                    <h3>Bloques que encajan bien en esta pantalla</h3>
                </div>
            </div>

            <ul class="bullet-list">
                @foreach ($checklist as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </article>

        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Notas del modulo</p>
                    <h3>Detalles utiles para las siguientes vistas</h3>
                </div>
            </div>

            <ul class="bullet-list bullet-list--muted">
                @foreach ($notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </article>
    </section>
@endsection
