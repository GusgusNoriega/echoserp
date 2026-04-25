@extends('layouts.admin')

@section('content')
    @includeWhen(! $isReady, 'admin.partials.access-warning')

    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Administracion</p>
            <h2>Define modulos primero y deja listo el catalogo de acciones que cada uno puede usar.</h2>
            <p class="section-copy">
                Esta capa evita que los permisos dependan de texto libre. Cada modulo decide que acciones admite y luego
                el formulario de permisos solo muestra opciones validas para ese contexto.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="module-create-modal"
                    @disabled(! $isReady)
                >
                    Nuevo modulo
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.permissions.index') }}">
                    Ver permisos
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.roles.index') }}">
                    Ver roles
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Catalogo estructural</span>
                <span class="pill">Acciones por select</span>
                <span class="pill">Slugs coherentes</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Catalogo base</p>
                    <h3>Acciones disponibles</h3>
                </div>
            </div>

            @if ($actionOptions->isNotEmpty())
                <ul class="stack-list">
                    @foreach ($actionOptions as $action)
                        <li>
                            <div>
                                <strong>{{ $action['name'] }}</strong>
                                <small>{{ $action['slug'] }}</small>
                            </div>

                            <strong>{{ $action['is_system'] ? 'Base' : 'Extra' }}</strong>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="empty-state">
                    Aun no hay acciones base registradas en el sistema.
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
        @forelse ($modules as $module)
            <article class="access-card">
                <div class="access-card__header">
                    <div>
                        <p class="section-kicker">{{ $module['is_system'] ? 'Modulo del sistema' : 'Modulo personalizado' }}</p>
                        <h3>{{ $module['name'] }}</h3>
                    </div>

                    <div class="card-actions">
                        <span class="nav-badge">{{ $module['permissions_count'] }} permisos</span>

                        <button
                            class="button-link button-link--ghost button-link--compact"
                            type="button"
                            data-modal-open="module-edit-modal-{{ $module['id'] }}"
                        >
                            Editar
                        </button>
                        <button
                            class="button-link button-link--danger button-link--compact"
                            type="button"
                            data-modal-open="module-delete-modal-{{ $module['id'] }}"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>

                <p class="access-card__copy">{{ $module['description'] ?: 'Sin descripcion registrada.' }}</p>

                <div class="access-card__stats">
                    <div>
                        <strong>{{ $module['actions_count'] }}</strong>
                        <span>acciones</span>
                    </div>
                    <div>
                        <strong>{{ $module['permissions_count'] }}</strong>
                        <span>permisos</span>
                    </div>
                </div>

                <div class="chip-list">
                    @forelse ($module['actions'] as $action)
                        <span class="chip @if ($action['is_system']) chip--accent @endif">
                            {{ $action['slug'] }}
                        </span>
                    @empty
                        <span class="chip chip--muted">Sin acciones</span>
                    @endforelse
                </div>
            </article>
        @empty
            <article class="panel-card">
                <p class="empty-state">
                    No hay modulos cargados todavia. El seeder base puede poblarlos o puedes crear los tuyos.
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

    @if ($isReady)
        <x-admin.modal
            id="module-create-modal"
            title="Nuevo modulo"
            description="Define el modulo y selecciona las acciones que quedaran disponibles para permisos."
            size="wide"
        >
            @include('admin.modules.partials.form', [
                'action' => route('admin.modules.store'),
                'method' => 'POST',
                'modalId' => 'module-create-modal',
                'errorBag' => 'moduleCreate',
                'submitLabel' => 'Guardar modulo',
                'module' => null,
                'actionOptions' => $actionOptions,
            ])
        </x-admin.modal>

        @foreach ($modules as $module)
            <x-admin.modal
                :id="'module-edit-modal-'.$module['id']"
                :title="'Editar modulo: '.$module['name']"
                description="Actualiza nombre, slug y acciones disponibles."
                size="wide"
            >
                @include('admin.modules.partials.form', [
                    'action' => route('admin.modules.update', $module['id']),
                    'method' => 'PUT',
                    'modalId' => 'module-edit-modal-'.$module['id'],
                    'errorBag' => 'moduleEdit',
                    'submitLabel' => 'Guardar cambios',
                    'module' => $module,
                    'actionOptions' => $actionOptions,
                ])
            </x-admin.modal>

            <x-admin.modal
                :id="'module-delete-modal-'.$module['id']"
                :title="'Eliminar modulo: '.$module['name']"
                description="Esta accion eliminara tambien los permisos que dependan de este modulo."
                kicker="Eliminacion"
            >
                <form class="modal-form" method="POST" action="{{ route('admin.modules.destroy', $module['id']) }}">
                    @csrf
                    @method('DELETE')

                    <div class="danger-note">
                        <strong>Se eliminara la estructura asociada.</strong>
                        <p>
                            El modulo <strong>{{ $module['name'] }}</strong> dejara de existir y sus permisos vinculados
                            tambien se eliminaran del catalogo.
                        </p>
                    </div>

                    <div class="danger-summary">
                        <div>
                            <strong>{{ $module['actions_count'] }}</strong>
                            <span>acciones configuradas</span>
                        </div>
                        <div>
                            <strong>{{ $module['permissions_count'] }}</strong>
                            <span>permisos afectados</span>
                        </div>
                    </div>

                    <div class="modal-form__footer">
                        <button class="button-link button-link--ghost" type="button" data-modal-close>
                            Cancelar
                        </button>
                        <button class="button-link button-link--danger" type="submit">
                            Eliminar modulo
                        </button>
                    </div>
                </form>
            </x-admin.modal>
        @endforeach
    @endif
@endsection
