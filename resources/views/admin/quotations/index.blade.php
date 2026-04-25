@extends('layouts.admin')

@section('content')
    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Documentos comerciales</p>
            <h2>Construye cotizaciones completas con cliente, items, plan de trabajo y condiciones listos para PDF.</h2>
            <p class="section-copy">
                Este modulo separa la cotizacion real del catalogo de productos y servicios. Cada documento conserva
                importes propios, datos manuales del cliente y un snapshot del emisor para historicos futuros.
            </p>

            <div class="hero-actions">
                <a class="button-link button-link--primary" href="{{ route('admin.quotations.create') }}">
                    Nueva cotizacion
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.catalog.index') }}">
                    Abrir catalogo
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.settings.index') }}">
                    Configuracion
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Cliente registrado o manual</span>
                <span class="pill">Items autocompletables</span>
                <span class="pill">Plan de trabajo</span>
                <span class="pill">Terminos y notas</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Emisor por defecto</p>
                    <h3>Resumen rapido</h3>
                </div>
            </div>

            <ul class="stack-list">
                <li>
                    <div>
                        <strong>{{ $settingsPreview['company_name'] ?: 'Sin empresa definida' }}</strong>
                        <small>{{ $settingsPreview['company_document_label'] }} {{ $settingsPreview['company_document_number'] ?: 'pendiente' }}</small>
                    </div>
                </li>
                <li>
                    <div>
                        <strong>{{ $settingsPreview['company_email'] ?: 'Sin correo' }}</strong>
                        <small>correo del emisor</small>
                    </div>
                </li>
                <li>
                    <div>
                        <strong>{{ $settingsPreview['default_currency'] ?: 'Sin moneda' }}</strong>
                        <small>moneda por defecto</small>
                    </div>
                </li>
                <li>
                    <div>
                        <strong>{{ $settingsPreview['default_validity_days'] }} dias</strong>
                        <small>vigencia sugerida</small>
                    </div>
                </li>
            </ul>
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

    @if ($quotations->isNotEmpty())
        <section class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Listado</p>
                    <h3>Cotizaciones registradas</h3>
                </div>

                <div class="hero-actions hero-actions--tight">
                    <a class="button-link button-link--ghost button-link--compact" href="{{ route('admin.quotations.currencies.index') }}">
                        Monedas
                    </a>
                    <a class="button-link button-link--ghost button-link--compact" href="{{ route('admin.quotations.catalog.index') }}">
                        Catalogo
                    </a>
                </div>
            </div>

            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nro.</th>
                            <th>Cliente y asunto</th>
                            <th>Fechas</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotations as $quotation)
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $quotation['number'] }}</strong>
                                        <span>{{ $quotation['line_items_count'] }} items</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $quotation['client_company_name'] }}</strong>
                                        <span>{{ $quotation['title'] }}</span>
                                        <span class="chip @if ($quotation['customer_id']) chip--accent @else chip--muted @endif">
                                            {{ $quotation['customer_label'] }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $quotation['issue_date'] }}</strong>
                                        <span>Vence: {{ $quotation['valid_until'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="chip @if ($quotation['status'] === 'draft') chip--muted @else chip--accent @endif">
                                        {{ $quotation['status_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $quotation['total_label'] }}</strong>
                                        <span>{{ $quotation['currency_label'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="button-link button-link--primary button-link--compact" href="{{ route('admin.quotations.pdf', $quotation['id']) }}">
                                            Generar PDF
                                        </a>
                                        <a class="button-link button-link--ghost button-link--compact" href="{{ route('admin.quotations.edit', $quotation['id']) }}">
                                            Editar
                                        </a>
                                        <button
                                            class="button-link button-link--danger button-link--compact"
                                            type="button"
                                            data-modal-open="quotation-delete-modal-{{ $quotation['id'] }}"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="panel-card">
            <p class="empty-state">
                Todavia no hay cotizaciones registradas. Crea la primera para empezar a estructurar documentos comerciales completos.
            </p>
        </section>
    @endif

    <section class="content-grid">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Siguiente implementacion</p>
                    <h3>Base lista para PDF y flujo comercial</h3>
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
                    <p class="section-kicker">Configuracion</p>
                    <h3>Datos corporativos por defecto</h3>
                </div>
            </div>

            <p class="empty-state">
                Define correo, RUC, direccion, prefijo y textos comerciales para que las nuevas cotizaciones salgan listas desde el primer guardado.
            </p>

            <div class="hero-actions">
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.settings.index') }}">
                    Ajustar configuracion
                </a>
            </div>
        </article>
    </section>

    @foreach ($quotations as $quotation)
        <x-admin.modal
            :id="'quotation-delete-modal-'.$quotation['id']"
            :title="'Eliminar cotizacion: '.$quotation['number']"
            description="Esta accion quitara la cotizacion junto con sus items y plan de trabajo."
            kicker="Eliminacion"
        >
            <form class="modal-form" method="POST" action="{{ route('admin.quotations.destroy', $quotation['id']) }}">
                @csrf
                @method('DELETE')

                <div class="danger-note">
                    <strong>Se eliminara el documento comercial.</strong>
                    <p>
                        La cotizacion <strong>{{ $quotation['number'] }}</strong> dejara de existir junto con sus items,
                        terminos y estructura de trabajo asociada.
                    </p>
                </div>

                <div class="danger-summary">
                    <div>
                        <strong>{{ $quotation['client_company_name'] }}</strong>
                        <span>cliente vinculado</span>
                    </div>
                    <div>
                        <strong>{{ $quotation['total_label'] }}</strong>
                        <span>monto actual</span>
                    </div>
                </div>

                <div class="modal-form__footer">
                    <button class="button-link button-link--ghost" type="button" data-modal-close>
                        Cancelar
                    </button>
                    <button class="button-link button-link--danger" type="submit">
                        Eliminar cotizacion
                    </button>
                </div>
            </form>
        </x-admin.modal>
    @endforeach
@endsection
