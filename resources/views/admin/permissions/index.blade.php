@extends('layouts.admin')

@section('content')
    @includeWhen(! $isReady, 'admin.partials.access-warning')

    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Administracion</p>
            <h2>Centraliza acciones por modulo para que cada nueva vista nazca con reglas claras.</h2>
            <p class="section-copy">
                El catalogo de permisos te permite definir acceso por accion y despues reutilizarlo en menu, rutas,
                policies o cualquier logica del panel sin volver a inventar la estructura.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="permission-create-modal"
                    @disabled(! $isReady)
                >
                    Nuevo permiso
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.modules.index') }}">
                    Ver modulos
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.roles.index') }}">
                    Revisar roles
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Slugs consistentes</span>
                <span class="pill">Agrupacion por modulo</span>
                <span class="pill">Listo para middleware</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Resumen</p>
                    <h3>Permisos por modulo</h3>
                </div>
            </div>

            @if ($permissionGroups->isNotEmpty())
                <ul class="stack-list">
                    @foreach ($permissionGroups as $group)
                        <li>
                            <div>
                                <strong>{{ $group['label'] }}</strong>
                                <small>{{ $group['count'] }} permisos definidos</small>
                            </div>

                            <strong>{{ $group['roles_count'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="empty-state">
                    Aun no hay permisos cargados por modulo. La vista ya esta preparada para organizarlos.
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

    <section class="module-grid permission-grid">
        @forelse ($permissionGroups as $group)
            <article class="access-card access-card--module">
                <div class="access-card__header">
                    <div>
                        <p class="section-kicker">{{ $group['count'] }} permisos</p>
                        <h3>{{ $group['label'] }}</h3>
                    </div>

                    <span class="nav-badge">{{ $group['roles_count'] }} roles</span>
                </div>

                <ul class="permission-list">
                    @foreach ($group['permissions'] as $permission)
                        <li>
                            <div class="permission-list__copy">
                                <strong>{{ $permission['name'] }}</strong>
                                <span>{{ $permission['slug'] }}</span>

                                @if (filled($permission['description']))
                                    <small>{{ $permission['description'] }}</small>
                                @endif
                            </div>

                            <div class="chip-list">
                                <span class="chip chip--accent">{{ $permission['action_name'] }}</span>

                                @forelse ($permission['roles'] as $role)
                                    <span class="chip">{{ $role['name'] }}</span>
                                @empty
                                    <span class="chip chip--muted">Sin rol</span>
                                @endforelse
                            </div>

                            <div class="table-actions">
                                <button
                                    class="button-link button-link--ghost button-link--compact"
                                    type="button"
                                    data-modal-open="permission-edit-modal-{{ $permission['id'] }}"
                                >
                                    Editar
                                </button>
                                <button
                                    class="button-link button-link--danger button-link--compact"
                                    type="button"
                                    data-modal-open="permission-delete-modal-{{ $permission['id'] }}"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </article>
        @empty
            <article class="panel-card">
                <p class="empty-state">
                    No hay permisos cargados todavia. Puedes registrar nuevos modulos desde el seeder o tu CRUD.
                </p>
            </article>
        @endforelse
    </section>

    @if ($isReady)
        <x-admin.modal
            id="permission-create-modal"
            title="Nuevo permiso"
            description="Elige el modulo y luego una accion disponible para generar un permiso coherente."
        >
            @include('admin.permissions.partials.form', [
                'action' => route('admin.permissions.store'),
                'method' => 'POST',
                'modalId' => 'permission-create-modal',
                'errorBag' => 'permissionCreate',
                'submitLabel' => 'Guardar permiso',
                'permission' => null,
                'moduleOptions' => $moduleOptions,
                'actionOptions' => $actionOptions,
            ])
        </x-admin.modal>

        @foreach ($permissionGroups as $group)
            @foreach ($group['permissions'] as $permission)
                <x-admin.modal
                    :id="'permission-edit-modal-'.$permission['id']"
                    :title="'Editar permiso: '.$permission['name']"
                    description="Ajusta modulo, accion o descripcion desde un popup."
                >
                    @include('admin.permissions.partials.form', [
                        'action' => route('admin.permissions.update', $permission['id']),
                        'method' => 'PUT',
                        'modalId' => 'permission-edit-modal-'.$permission['id'],
                        'errorBag' => 'permissionEdit',
                        'submitLabel' => 'Guardar cambios',
                        'permission' => $permission,
                        'moduleOptions' => $moduleOptions,
                        'actionOptions' => $actionOptions,
                    ])
                </x-admin.modal>

                <x-admin.modal
                    :id="'permission-delete-modal-'.$permission['id']"
                    :title="'Eliminar permiso: '.$permission['name']"
                    description="Esta accion quitara el permiso del catalogo y lo sacara de cualquier rol vinculado."
                    kicker="Eliminacion"
                >
                    <form class="modal-form" method="POST" action="{{ route('admin.permissions.destroy', $permission['id']) }}">
                        @csrf
                        @method('DELETE')

                        <div class="danger-note">
                            <strong>El slug dejara de estar disponible.</strong>
                            <p>
                                Se eliminara <strong>{{ $permission['slug'] }}</strong> y los roles que lo usen perderan
                                esa capacidad inmediatamente.
                            </p>
                        </div>

                        <div class="danger-summary">
                            <div>
                                <strong>{{ count($permission['roles']) }}</strong>
                                <span>roles afectados</span>
                            </div>
                            <div>
                                <strong>{{ $permission['action_name'] }}</strong>
                                <span>accion seleccionada</span>
                            </div>
                        </div>

                        <div class="modal-form__footer">
                            <button class="button-link button-link--ghost" type="button" data-modal-close>
                                Cancelar
                            </button>
                            <button class="button-link button-link--danger" type="submit">
                                Eliminar permiso
                            </button>
                        </div>
                    </form>
                </x-admin.modal>
            @endforeach
        @endforeach
    @endif
@endsection
