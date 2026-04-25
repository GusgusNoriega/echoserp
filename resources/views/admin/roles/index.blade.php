@extends('layouts.admin')

@section('content')
    @includeWhen(! $isReady, 'admin.partials.access-warning')

    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Administracion</p>
            <h2>Define perfiles reutilizables y deja lista la capa de acceso para futuras vistas.</h2>
            <p class="section-copy">
                Los roles concentran grupos de permisos para que los cambios del panel se manejen desde una sola
                pantalla, sin repetir reglas en cada modulo nuevo.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="role-create-modal"
                    @disabled(! $isReady)
                >
                    Nuevo rol
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.users.index') }}">
                    Ver usuarios
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.permissions.index') }}">
                    Ver permisos
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Perfiles base</span>
                <span class="pill">Cobertura por modulo</span>
                <span class="pill">Escalable a policies</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Cobertura</p>
                    <h3>Modulos cubiertos</h3>
                </div>
            </div>

            @if ($moduleCoverage->isNotEmpty())
                <ul class="stack-list">
                    @foreach ($moduleCoverage as $module)
                        <li>
                            <div>
                                <strong>{{ $module['label'] }}</strong>
                                <small>{{ $module['permissions_count'] }} permisos vinculados</small>
                            </div>

                            <strong>{{ $module['roles_count'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="empty-state">
                    Aun no hay cobertura cargada por modulo. La estructura ya esta preparada para mostrarla.
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
        @forelse ($roles as $role)
            <article class="access-card">
                <div class="access-card__header">
                    <div>
                        <p class="section-kicker">{{ $role['is_system'] ? 'Rol del sistema' : 'Rol personalizable' }}</p>
                        <h3>{{ $role['name'] }}</h3>
                    </div>

                    <div class="card-actions">
                        <span class="nav-badge">{{ $role['permissions_count'] }} permisos</span>

                        <button
                            class="button-link button-link--ghost button-link--compact"
                            type="button"
                            data-modal-open="role-edit-modal-{{ $role['id'] }}"
                        >
                            Editar
                        </button>
                        <button
                            class="button-link button-link--danger button-link--compact"
                            type="button"
                            data-modal-open="role-delete-modal-{{ $role['id'] }}"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>

                <p class="access-card__copy">{{ $role['description'] }}</p>

                <div class="access-card__stats">
                    <div>
                        <strong>{{ $role['users_count'] }}</strong>
                        <span>usuarios</span>
                    </div>
                    <div>
                        <strong>{{ $role['module_count'] }}</strong>
                        <span>modulos</span>
                    </div>
                </div>

                <div class="chip-list">
                    @foreach ($role['preview_permissions'] as $permission)
                        <span class="chip chip--accent">{{ $permission['name'] }}</span>
                    @endforeach

                    @if ($role['remaining_permissions'] > 0)
                        <span class="chip chip--muted">+{{ $role['remaining_permissions'] }} mas</span>
                    @endif
                </div>
            </article>
        @empty
            <article class="panel-card">
                <p class="empty-state">
                    No hay roles cargados todavia. Puedes sembrar ejemplos o enlazar tu propio formulario de alta.
                </p>
            </article>
        @endforelse
    </section>

    <section class="content-grid content-grid--single">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Siguiente implementacion</p>
                    <h3>Pasos recomendados para este modulo</h3>
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
            id="role-create-modal"
            title="Nuevo rol"
            description="Define el perfil y marca sus permisos desde un popup."
            size="wide"
        >
            @include('admin.roles.partials.form', [
                'action' => route('admin.roles.store'),
                'method' => 'POST',
                'modalId' => 'role-create-modal',
                'errorBag' => 'roleCreate',
                'submitLabel' => 'Guardar rol',
                'role' => null,
                'permissionOptions' => $permissionOptions,
            ])
        </x-admin.modal>

        @foreach ($roles as $role)
            <x-admin.modal
                :id="'role-edit-modal-'.$role['id']"
                :title="'Editar rol: '.$role['name']"
                description="Actualiza nombre, slug, tipo y permisos asignados."
                size="wide"
            >
                @include('admin.roles.partials.form', [
                    'action' => route('admin.roles.update', $role['id']),
                    'method' => 'PUT',
                    'modalId' => 'role-edit-modal-'.$role['id'],
                    'errorBag' => 'roleEdit',
                    'submitLabel' => 'Guardar cambios',
                    'role' => $role,
                    'permissionOptions' => $permissionOptions,
                ])
            </x-admin.modal>

            <x-admin.modal
                :id="'role-delete-modal-'.$role['id']"
                :title="'Eliminar rol: '.$role['name']"
                description="Esta accion quitara el rol del catalogo y lo desvinculara de usuarios y permisos."
                kicker="Eliminacion"
            >
                <form class="modal-form" method="POST" action="{{ route('admin.roles.destroy', $role['id']) }}">
                    @csrf
                    @method('DELETE')

                    <div class="danger-note">
                        <strong>Vas a eliminar un perfil reutilizable.</strong>
                        <p>
                            El rol <strong>{{ $role['name'] }}</strong> dejara de existir y ya no podra asignarse a
                            usuarios ni usarse como agrupador de permisos.
                        </p>
                    </div>

                    <div class="danger-summary">
                        <div>
                            <strong>{{ $role['users_count'] }}</strong>
                            <span>usuarios vinculados</span>
                        </div>
                        <div>
                            <strong>{{ $role['permissions_count'] }}</strong>
                            <span>permisos asociados</span>
                        </div>
                    </div>

                    <div class="modal-form__footer">
                        <button class="button-link button-link--ghost" type="button" data-modal-close>
                            Cancelar
                        </button>
                        <button class="button-link button-link--danger" type="submit">
                            Eliminar rol
                        </button>
                    </div>
                </form>
            </x-admin.modal>
        @endforeach
    @endif
@endsection
