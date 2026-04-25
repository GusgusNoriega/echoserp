@php
    $bag = $errors->getBag($errorBag);
    $defaults = $user ?? [];
    $reopened = session('modal') === $modalId;
    $selectedRoleIds = $reopened
        ? collect(old('role_ids', []))->map(static fn ($id) => (int) $id)->all()
        : ($defaults['role_ids'] ?? []);
    $nameValue = $reopened ? old('name', $defaults['name'] ?? '') : ($defaults['name'] ?? '');
    $emailValue = $reopened ? old('email', $defaults['email'] ?? '') : ($defaults['email'] ?? '');
    $verifiedRaw = $reopened
        ? old('email_verified', ! empty($defaults['email_verified']) ? '1' : null)
        : (! empty($defaults['email_verified']) ? '1' : null);
    $isVerified = in_array($verifiedRaw, [true, 1, '1', 'on'], true);
    $isEditing = ! empty($defaults['id']);
@endphp

<form class="modal-form" method="POST" action="{{ $action }}">
    @csrf

    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Nombre completo</span>
            <input type="text" name="name" value="{{ $nameValue }}" autocomplete="name" required data-modal-focus>

            @if ($bag->has('name'))
                <small class="field-error">{{ $bag->first('name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Correo</span>
            <input type="email" name="email" value="{{ $emailValue }}" autocomplete="email" required>

            @if ($bag->has('email'))
                <small class="field-error">{{ $bag->first('email') }}</small>
            @endif
        </label>
    </div>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>{{ $isEditing ? 'Nueva contrasena' : 'Contrasena' }}</span>
            <input
                type="password"
                name="password"
                autocomplete="new-password"
                @if (! $isEditing) required @endif
            >
            <small class="form-help">
                {{ $isEditing ? 'Dejalo vacio si no deseas cambiar la contrasena.' : 'Usa al menos 8 caracteres.' }}
            </small>

            @if ($bag->has('password'))
                <small class="field-error">{{ $bag->first('password') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Confirmar contrasena</span>
            <input
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                @if (! $isEditing) required @endif
            >
        </label>
    </div>

    <fieldset class="form-section">
        <legend>Roles asignados</legend>

        <div class="choice-grid">
            @forelse ($roleOptions as $role)
                <label class="choice-card">
                    <input
                        type="checkbox"
                        name="role_ids[]"
                        value="{{ $role['id'] }}"
                        @checked(in_array($role['id'], $selectedRoleIds, true))
                    >

                    <span class="choice-card__copy">
                        <strong>{{ $role['name'] }}</strong>
                        <small>{{ $role['description'] ?: $role['slug'] }}</small>
                    </span>
                </label>
            @empty
                <p class="empty-state">
                    Todavia no hay roles disponibles. Crea al menos uno para asignarlo a usuarios.
                </p>
            @endforelse
        </div>

        @if ($bag->has('role_ids') || $bag->has('role_ids.*'))
            <small class="field-error">{{ $bag->first('role_ids') ?: $bag->first('role_ids.*') }}</small>
        @endif
    </fieldset>

    <label class="toggle-field">
        <input type="checkbox" name="email_verified" value="1" @checked($isVerified)>
        <span>Marcar correo como verificado</span>
    </label>

    <div class="modal-form__footer">
        <button class="button-link button-link--ghost button-link--compact" type="button" data-modal-close>
            Cancelar
        </button>

        <button class="button-link button-link--primary button-link--compact" type="submit">
            {{ $submitLabel }}
        </button>
    </div>
</form>
