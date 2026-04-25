@extends('layouts.admin')

@section('content')
    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Soporte de precios</p>
            <h2>Administra las monedas que podran usar los productos y servicios del modulo de cotizaciones.</h2>
            <p class="section-copy">
                Este catalogo controla los tipos de moneda disponibles para los precios del portafolio comercial y evita
                escribir codigos o simbolos manualmente en cada item.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="currency-create-modal"
                >
                    Nueva moneda
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.catalog.index') }}">
                    Volver al catalogo
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.settings.index') }}">
                    Configuracion
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Codigos unificados</span>
                <span class="pill">Simbolos visibles</span>
                <span class="pill">Uso por item</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Uso actual</p>
                    <h3>Resumen rapido</h3>
                </div>
            </div>

            @if ($currencies->isNotEmpty())
                <ul class="signal-list">
                    @foreach ($currencies->take(4) as $currency)
                        <li>
                            <span>{{ $currency['code'] }}</span>
                            <strong>{{ $currency['quotation_items_count'] }} items</strong>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="empty-state">
                    Aun no hay monedas creadas en el sistema.
                </p>
            @endif
        </aside>
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

    <section class="module-grid module-grid--roles">
        @forelse ($currencies as $currency)
            <article class="access-card">
                <div class="access-card__header">
                    <div>
                        <p class="section-kicker">{{ $currency['status_label'] }}</p>
                        <h3>{{ $currency['name'] }}</h3>
                    </div>

                    <div class="card-actions">
                        <span class="nav-badge">{{ $currency['quotation_items_count'] }} items</span>

                        <button
                            class="button-link button-link--ghost button-link--compact"
                            type="button"
                            data-modal-open="currency-edit-modal-{{ $currency['id'] }}"
                        >
                            Editar
                        </button>
                        <button
                            class="button-link button-link--danger button-link--compact"
                            type="button"
                            data-modal-open="currency-delete-modal-{{ $currency['id'] }}"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>

                <p class="access-card__copy">
                    Codigo <strong>{{ $currency['code'] }}</strong>
                    @if ($currency['symbol'])
                        con simbolo <strong>{{ $currency['symbol'] }}</strong>.
                    @else
                        sin simbolo visual configurado.
                    @endif
                </p>

                <div class="access-card__stats">
                    <div>
                        <strong>{{ $currency['label'] }}</strong>
                        <span>identificador visible</span>
                    </div>
                    <div>
                        <strong>{{ $currency['updated_at'] }}</strong>
                        <span>ultima actualizacion</span>
                    </div>
                </div>
            </article>
        @empty
            <article class="panel-card">
                <p class="empty-state">
                    Todavia no hay monedas registradas. Crea la primera para comenzar a asignar precios en el catalogo.
                </p>
            </article>
        @endforelse
    </section>

    <section class="content-grid content-grid--single">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Siguiente implementacion</p>
                    <h3>Como mantener limpio este catalogo</h3>
                </div>
            </div>

            <ul class="bullet-list">
                @foreach ($nextSteps as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ul>
        </article>
    </section>

    <x-admin.modal
        id="currency-create-modal"
        title="Nueva moneda"
        description="Registra el codigo y simbolo que usaran los precios del catalogo."
    >
        @include('admin.quotations.partials.currency-form', [
            'action' => route('admin.quotations.currencies.store'),
            'method' => 'POST',
            'modalId' => 'currency-create-modal',
            'errorBag' => 'currencyCreate',
            'submitLabel' => 'Guardar moneda',
            'currency' => null,
        ])
    </x-admin.modal>

    @foreach ($currencies as $currency)
        <x-admin.modal
            :id="'currency-edit-modal-'.$currency['id']"
            :title="'Editar moneda: '.$currency['name']"
            description="Actualiza nombre, codigo, simbolo y estado de disponibilidad."
        >
            @include('admin.quotations.partials.currency-form', [
                'action' => route('admin.quotations.currencies.update', $currency['id']),
                'method' => 'PUT',
                'modalId' => 'currency-edit-modal-'.$currency['id'],
                'errorBag' => 'currencyEdit',
                'submitLabel' => 'Guardar cambios',
                'currency' => $currency,
            ])
        </x-admin.modal>

        <x-admin.modal
            :id="'currency-delete-modal-'.$currency['id']"
            :title="'Eliminar moneda: '.$currency['name']"
            description="Solo se puede eliminar si no esta asignada a ningun producto o servicio."
            kicker="Eliminacion"
        >
            <form class="modal-form" method="POST" action="{{ route('admin.quotations.currencies.destroy', $currency['id']) }}">
                @csrf
                @method('DELETE')

                <div class="danger-note">
                    <strong>Se eliminara la moneda del catalogo.</strong>
                    <p>
                        La moneda <strong>{{ $currency['name'] }}</strong> con codigo
                        <strong>{{ $currency['code'] }}</strong> dejara de estar disponible para nuevos precios.
                    </p>
                </div>

                <div class="danger-summary">
                    <div>
                        <strong>{{ $currency['label'] }}</strong>
                        <span>identificador actual</span>
                    </div>
                    <div>
                        <strong>{{ $currency['quotation_items_count'] }}</strong>
                        <span>items vinculados</span>
                    </div>
                </div>

                <div class="modal-form__footer">
                    <button class="button-link button-link--ghost" type="button" data-modal-close>
                        Cancelar
                    </button>
                    <button class="button-link button-link--danger" type="submit">
                        Eliminar moneda
                    </button>
                </div>
            </form>
        </x-admin.modal>
    @endforeach
@endsection
