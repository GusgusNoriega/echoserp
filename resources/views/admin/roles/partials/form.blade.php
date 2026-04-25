@php
    $bag = $errors->getBag($errorBag);
    $defaults = $role ?? [];
    $reopened = session('modal') === $modalId;
    $selectedPermissionIds = $reopened
        ? collect(old('permission_ids', []))->map(static fn ($id) => (int) $id)->all()
        : ($defaults['permission_ids'] ?? []);
    $nameValue = $reopened ? old('name', $defaults['name'] ?? '') : ($defaults['name'] ?? '');
    $slugValue = $reopened ? old('slug', $defaults['slug'] ?? '') : ($defaults['slug'] ?? '');
    $descriptionValue = $reopened ? old('description', $defaults['description'] ?? '') : ($defaults['description'] ?? '');
    $isSystemRaw = $reopened
        ? old('is_system', ! empty($defaults['is_system']) ? '1' : null)
        : (! empty($defaults['is_system']) ? '1' : null);
    $isSystem = in_array($isSystemRaw, [true, 1, '1', 'on'], true);
@endphp

<form class="modal-form" method="POST" action="{{ $action }}">
    @csrf

    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Nombre del rol</span>
            <input type="text" name="name" value="{{ $nameValue }}" required data-modal-focus>

            @if ($bag->has('name'))
                <small class="field-error">{{ $bag->first('name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Slug</span>
            <input type="text" name="slug" value="{{ $slugValue }}" placeholder="super-admin">
            <small class="form-help">Si lo dejas vacio se genera a partir del nombre.</small>

            @if ($bag->has('slug'))
                <small class="field-error">{{ $bag->first('slug') }}</small>
            @endif
        </label>
    </div>

    <label class="form-field">
        <span>Descripcion</span>
        <textarea name="description" rows="3" placeholder="Describe para que sirve este rol.">{{ $descriptionValue }}</textarea>

        @if ($bag->has('description'))
            <small class="field-error">{{ $bag->first('description') }}</small>
        @endif
    </label>

    <label class="toggle-field">
        <input type="checkbox" name="is_system" value="1" @checked($isSystem)>
        <span>Rol del sistema</span>
    </label>

    <fieldset class="form-section">
        <legend>Permisos asignados</legend>

        <div class="choice-stack">
            @forelse ($permissionOptions as $group)
                <section class="choice-group">
                    <header class="choice-group__header">
                        <strong>{{ $group['label'] }}</strong>
                        <small>{{ count($group['permissions']) }} permisos</small>
                    </header>

                    <div class="choice-grid">
                        @foreach ($group['permissions'] as $permission)
                            <label class="choice-card">
                                <input
                                    type="checkbox"
                                    name="permission_ids[]"
                                    value="{{ $permission['id'] }}"
                                    @checked(in_array($permission['id'], $selectedPermissionIds, true))
                                >

                                <span class="choice-card__copy">
                                    <strong>{{ $permission['name'] }}</strong>
                                    <small>{{ $permission['slug'] }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="empty-state">
                    Aun no hay permisos creados. Primero registra el catalogo base y luego asignalos a roles.
                </p>
            @endforelse
        </div>

        @if ($bag->has('permission_ids') || $bag->has('permission_ids.*'))
            <small class="field-error">{{ $bag->first('permission_ids') ?: $bag->first('permission_ids.*') }}</small>
        @endif
    </fieldset>

    <div class="modal-form__footer">
        <button class="button-link button-link--ghost button-link--compact" type="button" data-modal-close>
            Cancelar
        </button>

        <button class="button-link button-link--primary button-link--compact" type="submit">
            {{ $submitLabel }}
        </button>
    </div>
</form>
