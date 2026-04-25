@extends('layouts.admin')

@section('content')
    @includeWhen(! $isReady, 'admin.partials.access-warning')

    <section class="hero-grid hero-grid--single">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Administracion</p>
            <h2>Gestiona el equipo desde una base pensada para crecer con roles y permisos reutilizables.</h2>
            <p class="section-copy">
                Esta seccion ya separa usuarios, perfiles y cobertura efectiva para que cualquier nueva vista del panel
                pueda conectarse despues sin rehacer la estructura.
            </p>

            <div class="hero-actions">
                <button
                    class="button-link button-link--primary"
                    type="button"
                    data-modal-open="user-create-modal"
                    @disabled(! $isReady)
                >
                    Nuevo usuario
                </button>
                <a class="button-link button-link--ghost" href="{{ route('admin.roles.index') }}">
                    Ver roles
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.permissions.index') }}">
                    Ver permisos
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

    <section class="content-grid content-grid--single">
        <article class="panel-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Equipo</p>
                    <h3>Listado base de usuarios</h3>
                </div>
            </div>

            @if ($users->isNotEmpty())
                <div class="table-shell">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Roles</th>
                                <th>Permisos</th>
                                <th>Estado</th>
                                <th>Alta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <div class="table-user">
                                            <strong>{{ $user['name'] }}</strong>
                                            <span>{{ $user['email'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="chip-list">
                                            @forelse ($user['roles'] as $role)
                                                <span class="chip @if ($role['is_system']) chip--accent @endif">
                                                    {{ $role['name'] }}
                                                </span>
                                            @empty
                                                <span class="chip chip--muted">Sin rol</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-metric">
                                            <strong>{{ $user['permissions_count'] }}</strong>
                                            <span>{{ $user['permission_modules_count'] }} modulos</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-pill status-pill--{{ $user['status_key'] }}">
                                            {{ $user['status_label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="table-hint">{{ $user['created_at'] }}</span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button
                                                class="button-link button-link--ghost button-link--compact"
                                                type="button"
                                                data-modal-open="user-edit-modal-{{ $user['id'] }}"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                class="button-link button-link--danger button-link--compact"
                                                type="button"
                                                data-modal-open="user-delete-modal-{{ $user['id'] }}"
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
                    No hay usuarios cargados todavia. Puedes poblar ejemplos con el seeder o conectar tu propio CRUD.
                </p>
            @endif
        </article>
    </section>

    @if ($isReady)
        <x-admin.modal
            id="user-create-modal"
            title="Nuevo usuario"
            description="Crea una cuenta y asigna sus roles iniciales desde esta misma vista."
            size="wide"
        >
            @include('admin.users.partials.form', [
                'action' => route('admin.users.store'),
                'method' => 'POST',
                'modalId' => 'user-create-modal',
                'errorBag' => 'userCreate',
                'submitLabel' => 'Guardar usuario',
                'user' => null,
                'roleOptions' => $roleOptions,
            ])
        </x-admin.modal>

        @foreach ($users as $user)
            <x-admin.modal
                :id="'user-edit-modal-'.$user['id']"
                :title="'Editar usuario: '.$user['name']"
                description="Actualiza datos, estado de verificacion y roles desde un popup."
                size="wide"
            >
                @include('admin.users.partials.form', [
                    'action' => route('admin.users.update', $user['id']),
                    'method' => 'PUT',
                    'modalId' => 'user-edit-modal-'.$user['id'],
                    'errorBag' => 'userEdit',
                    'submitLabel' => 'Guardar cambios',
                    'user' => $user,
                    'roleOptions' => $roleOptions,
                ])
            </x-admin.modal>

            <x-admin.modal
                :id="'user-delete-modal-'.$user['id']"
                :title="'Eliminar usuario: '.$user['name']"
                description="Esta accion quitara la cuenta del panel y limpiara sus asignaciones de roles."
                kicker="Eliminacion"
            >
                <form class="modal-form" method="POST" action="{{ route('admin.users.destroy', $user['id']) }}">
                    @csrf
                    @method('DELETE')

                    <div class="danger-note">
                        <strong>Esta accion no se puede deshacer.</strong>
                        <p>
                            Se eliminara a <strong>{{ $user['name'] }}</strong> con correo
                            <code>{{ $user['email'] }}</code> y se quitaran sus relaciones de acceso.
                        </p>
                    </div>

                    <div class="danger-summary">
                        <div>
                            <strong>{{ count($user['roles']) }}</strong>
                            <span>roles asignados</span>
                        </div>
                        <div>
                            <strong>{{ $user['permissions_count'] }}</strong>
                            <span>permisos efectivos</span>
                        </div>
                    </div>

                    <div class="modal-form__footer">
                        <button class="button-link button-link--ghost" type="button" data-modal-close>
                            Cancelar
                        </button>
                        <button class="button-link button-link--danger" type="submit">
                            Eliminar usuario
                        </button>
                    </div>
                </form>
            </x-admin.modal>
        @endforeach
    @endif
@endsection
