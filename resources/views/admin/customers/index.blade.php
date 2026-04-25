@extends('layouts.admin')

@section('content')
    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Clientes</p>
            <h2>Centraliza clientes y reutiliza sus datos en cotizaciones sin bloquear la captura manual.</h2>
            <p class="section-copy">
                Cada cliente conserva razon social, documento, contacto, correo, telefono y direccion. Al cotizar puedes
                elegir un cliente registrado o dejar la cotizacion como cliente manual.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="customer-create-modal"
                >
                    Nuevo cliente
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.create') }}">
                    Nueva cotizacion
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.index') }}">
                    Ver cotizaciones
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Relacion opcional</span>
                <span class="pill">Autocompletado</span>
                <span class="pill">Snapshot editable</span>
                <span class="pill">Cotizacion manual</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Actividad</p>
                    <h3>Clientes recientes</h3>
                </div>
            </div>

            @if ($recentCustomers->isNotEmpty())
                <ul class="stack-list">
                    @foreach ($recentCustomers as $customer)
                        <li>
                            <div>
                                <strong>{{ $customer['company_name'] }}</strong>
                                <small>{{ $customer['email'] ?: ($customer['phone'] ?: 'Sin contacto') }}</small>
                            </div>

                            <span class="chip @if ($customer['is_active']) chip--accent @else chip--muted @endif">
                                {{ $customer['status_label'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="empty-state">
                    Todavia no hay clientes registrados. Crea el primero para empezar a reutilizarlo en cotizaciones.
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

    <section class="panel-card">
        <div class="panel-heading">
            <div>
                <p class="section-kicker">Listado</p>
                <h3>Clientes registrados</h3>
            </div>
        </div>

        @if ($customers->isNotEmpty())
            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th>Contacto</th>
                            <th>Cotizaciones</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $customer['company_name'] }}</strong>
                                        <span>{{ $customer['address'] ?: 'Sin direccion' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $customer['document_label'] }}</strong>
                                        <span>{{ $customer['document_number'] ?: 'Sin numero' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-user">
                                        <strong>{{ $customer['contact_name'] ?: 'Sin contacto' }}</strong>
                                        <span>{{ collect([$customer['email'], $customer['phone']])->filter()->implode(' | ') ?: 'Sin datos' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-metric">
                                        <strong>{{ $customer['quotations_count'] }}</strong>
                                        <span>relacionadas</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="chip @if ($customer['is_active']) chip--accent @else chip--muted @endif">
                                        {{ $customer['status_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button
                                            class="button-link button-link--ghost button-link--compact"
                                            type="button"
                                            data-modal-open="customer-edit-modal-{{ $customer['id'] }}"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            class="button-link button-link--danger button-link--compact"
                                            type="button"
                                            data-modal-open="customer-delete-modal-{{ $customer['id'] }}"
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
        @else
            <p class="empty-state">
                Todavia no hay clientes registrados. Usa el boton superior para crear el primer cliente.
            </p>
        @endif
    </section>

    <x-admin.modal
        id="customer-create-modal"
        title="Nuevo cliente"
        description="Registra la ficha comercial para reutilizarla al crear o editar cotizaciones."
        size="wide"
    >
        @include('admin.customers.partials.form', [
            'action' => route('admin.customers.store'),
            'method' => 'POST',
            'modalId' => 'customer-create-modal',
            'errorBag' => 'customerCreate',
            'submitLabel' => 'Guardar cliente',
            'customer' => null,
        ])
    </x-admin.modal>

    @foreach ($customers as $customer)
        <x-admin.modal
            :id="'customer-edit-modal-'.$customer['id']"
            :title="'Editar cliente: '.$customer['company_name']"
            description="Actualiza los datos que podran autocompletarse en nuevas cotizaciones."
            size="wide"
        >
            @include('admin.customers.partials.form', [
                'action' => route('admin.customers.update', $customer['id']),
                'method' => 'PUT',
                'modalId' => 'customer-edit-modal-'.$customer['id'],
                'errorBag' => 'customerEdit',
                'submitLabel' => 'Guardar cambios',
                'customer' => $customer,
            ])
        </x-admin.modal>

        <x-admin.modal
            :id="'customer-delete-modal-'.$customer['id']"
            :title="'Eliminar cliente: '.$customer['company_name']"
            description="La ficha se eliminara. Las cotizaciones existentes conservaran sus datos manuales guardados."
            kicker="Eliminacion"
        >
            <form class="modal-form" method="POST" action="{{ route('admin.customers.destroy', $customer['id']) }}">
                @csrf
                @method('DELETE')

                <div class="danger-note">
                    <strong>Se eliminara la ficha del cliente.</strong>
                    <p>
                        Las cotizaciones relacionadas perderan el vinculo interno, pero mantendran razon social,
                        documento, correo, telefono y direccion guardados en cada documento.
                    </p>
                </div>

                <div class="danger-summary">
                    <div>
                        <strong>{{ $customer['quotations_count'] }}</strong>
                        <span>cotizaciones relacionadas</span>
                    </div>
                    <div>
                        <strong>{{ $customer['status_label'] }}</strong>
                        <span>estado actual</span>
                    </div>
                </div>

                <div class="modal-form__footer">
                    <button class="button-link button-link--ghost" type="button" data-modal-close>
                        Cancelar
                    </button>
                    <button class="button-link button-link--danger" type="submit">
                        Eliminar cliente
                    </button>
                </div>
            </form>
        </x-admin.modal>
    @endforeach
@endsection
