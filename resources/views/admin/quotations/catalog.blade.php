@extends('layouts.admin')

@section('content')
    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Catalogo comercial</p>
            <h2>Centraliza productos y servicios para que el equipo cotice con fichas limpias y reutilizables.</h2>
            <p class="section-copy">
                Cada elemento puede guardar imagen, descripcion, unidad, especificaciones y precio opcional con su moneda.
                Esto deja listo un catalogo base para futuras cotizaciones, propuestas o listas de precios.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="quotation-item-create-modal"
                >
                    Nuevo producto o servicio
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.index') }}">
                    Ver cotizaciones
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.currencies.index') }}">
                    Gestionar monedas
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Imagen comercial</span>
                <span class="pill">Unidad sugerida</span>
                <span class="pill">Especificaciones por linea</span>
                <span class="pill">Catalogo reutilizable</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Cobertura actual</p>
                    <h3>Monedas disponibles</h3>
                </div>
            </div>

            @if ($currencyOptions->isNotEmpty())
                <ul class="stack-list">
                    @foreach ($currencyOptions->take(5) as $currency)
                        <li>
                            <div>
                                <strong>{{ $currency['name'] }}</strong>
                                <small>{{ $currency['code'] }} @if ($currency['symbol']) - {{ $currency['symbol'] }} @endif</small>
                            </div>

                            <strong>{{ $currency['is_active'] ? 'Activa' : 'Inactiva' }}</strong>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="empty-state">
                    Aun no hay monedas registradas. Crea al menos una antes de cargar precios al catalogo.
                </p>
            @endif
        </aside>
    </section>

    <section class="metrics-grid">
        @foreach ($metrics as $metric)
            <article class="metric-card">
                <strong>{{ $metric['value'] }}</strong>
                <span>{{ $metric['label'] }}</span>
                <small>{{ $metric['detail'] }}</small>
            </article>
        @endforeach
    </section>

    <section class="quotation-grid">
        @forelse ($items as $item)
            <article class="quotation-card">
                <div class="quotation-card__media">
                    @if ($item['image_url'])
                        <img src="{{ $item['image_url'] }}" alt="Imagen de {{ $item['name'] }}">
                    @else
                        <div class="quotation-card__placeholder">
                            <strong>{{ $item['type_label'] }}</strong>
                            <span>Sin imagen cargada</span>
                        </div>
                    @endif
                </div>

                <div class="quotation-card__body">
                    <div class="quotation-card__header">
                        <div class="quotation-card__title">
                            <div class="chip-list">
                                <span class="chip chip--accent">{{ $item['type_label'] }}</span>
                                <span class="chip">{{ $item['status_label'] }}</span>
                            </div>

                            <h3>{{ $item['name'] }}</h3>
                            <small>Actualizado el {{ $item['updated_at'] }}</small>
                        </div>

                        <div class="card-actions">
                            <button
                                class="button-link button-link--ghost button-link--compact"
                                type="button"
                                data-modal-open="quotation-item-edit-modal-{{ $item['id'] }}"
                            >
                                Editar
                            </button>
                            <button
                                class="button-link button-link--danger button-link--compact"
                                type="button"
                                data-modal-open="quotation-item-delete-modal-{{ $item['id'] }}"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <p class="quotation-card__description">{{ $item['description'] }}</p>

                    <div class="quotation-card__stats">
                        <div>
                            <strong>{{ $item['price_label'] ?? 'Sin precio' }}</strong>
                            <span>Precio actual</span>
                        </div>
                        <div>
                            <strong>{{ $item['specifications_count'] }}</strong>
                            <span>Especificaciones</span>
                        </div>
                    </div>

                    <div class="quotation-card__section">
                        <strong>Unidad sugerida</strong>
                        <p>{{ $item['unit_label'] ?? 'Sin unidad definida' }}</p>
                    </div>

                    <div class="quotation-card__section">
                        <strong>Moneda</strong>
                        <p>{{ $item['currency_label'] ?? 'Sin moneda asignada' }}</p>
                    </div>

                    <div class="quotation-card__section">
                        <strong>Especificaciones</strong>

                        @if ($item['specifications'])
                            <ul class="spec-list">
                                @foreach ($item['specifications'] as $specification)
                                    <li>{{ $specification }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>Este item no tiene especificaciones registradas.</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <article class="panel-card">
                <p class="empty-state">
                    Todavia no hay productos o servicios cargados. Usa el boton superior para crear tu primer item del catalogo.
                </p>
            </article>
        @endforelse
    </section>

    <section class="content-grid">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Siguiente implementacion</p>
                    <h3>Buenas practicas para este catalogo</h3>
                </div>
            </div>

            <ul class="bullet-list">
                @foreach ($nextSteps as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ul>
        </article>

        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Monedas</p>
                    <h3>Enlace rapido al soporte de precios</h3>
                </div>
            </div>

            <p class="empty-state">
                Si un item necesita precio, primero verifica que la moneda exista en el catalogo y este activa para nuevas cargas.
            </p>

            <div class="hero-actions">
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.currencies.index') }}">
                    Abrir modulo de monedas
                </a>
            </div>
        </article>
    </section>

    <x-admin.modal
        id="quotation-item-create-modal"
        title="Nuevo producto o servicio"
        description="Carga la ficha comercial con descripcion, unidad, especificaciones, imagen y precio opcional."
        size="wide"
    >
        @include('admin.quotations.partials.item-form', [
            'action' => route('admin.quotations.catalog.items.store'),
            'method' => 'POST',
            'modalId' => 'quotation-item-create-modal',
            'errorBag' => 'quotationItemCreate',
            'submitLabel' => 'Guardar item',
            'item' => null,
            'currencyOptions' => $currencyOptions,
        ])
    </x-admin.modal>

    @foreach ($items as $item)
        <x-admin.modal
            :id="'quotation-item-edit-modal-'.$item['id']"
            :title="'Editar item: '.$item['name']"
            description="Actualiza tipo, contenido comercial, imagen y precio del item."
            size="wide"
        >
            @include('admin.quotations.partials.item-form', [
                'action' => route('admin.quotations.catalog.items.update', $item['id']),
                'method' => 'PUT',
                'modalId' => 'quotation-item-edit-modal-'.$item['id'],
                'errorBag' => 'quotationItemEdit',
                'submitLabel' => 'Guardar cambios',
                'item' => $item,
                'currencyOptions' => $currencyOptions,
            ])
        </x-admin.modal>

        <x-admin.modal
            :id="'quotation-item-delete-modal-'.$item['id']"
            :title="'Eliminar item: '.$item['name']"
            description="Esta accion quitara el registro del catalogo y eliminara su imagen asociada."
            kicker="Eliminacion"
        >
            <form class="modal-form" method="POST" action="{{ route('admin.quotations.catalog.items.destroy', $item['id']) }}">
                @csrf
                @method('DELETE')

                <div class="danger-note">
                    <strong>Se eliminara la ficha comercial.</strong>
                    <p>
                        El item <strong>{{ $item['name'] }}</strong> saldra del catalogo junto con sus especificaciones,
                        precio registrado e imagen cargada.
                    </p>
                </div>

                <div class="danger-summary">
                    <div>
                        <strong>{{ $item['type_label'] }}</strong>
                        <span>tipo de registro</span>
                    </div>
                    <div>
                        <strong>{{ $item['price_label'] ?? 'Sin precio' }}</strong>
                        <span>precio actual</span>
                    </div>
                </div>

                <div class="modal-form__footer">
                    <button class="button-link button-link--ghost" type="button" data-modal-close>
                        Cancelar
                    </button>
                    <button class="button-link button-link--danger" type="submit">
                        Eliminar item
                    </button>
                </div>
            </form>
        </x-admin.modal>
    @endforeach
@endsection
