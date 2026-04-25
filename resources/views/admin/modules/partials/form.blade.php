@php
    $bag = $errors->getBag($errorBag);
    $defaults = $module ?? [];
    $reopened = session('modal') === $modalId;
    $selectedActionIds = $reopened
        ? collect(old('action_ids', []))->map(static fn ($id) => (int) $id)->all()
        : ($defaults['action_ids'] ?? []);
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
            <span>Nombre del modulo</span>
            <input type="text" name="name" value="{{ $nameValue }}" required data-modal-focus>

            @if ($bag->has('name'))
                <small class="field-error">{{ $bag->first('name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Slug</span>
            <input type="text" name="slug" value="{{ $slugValue }}" placeholder="clientes">
            <small class="form-help">Si lo dejas vacio se genera a partir del nombre.</small>

            @if ($bag->has('slug'))
                <small class="field-error">{{ $bag->first('slug') }}</small>
            @endif
        </label>
    </div>

    <label class="form-field">
        <span>Descripcion</span>
        <textarea name="description" rows="3" placeholder="Explica para que sirve este modulo.">{{ $descriptionValue }}</textarea>

        @if ($bag->has('description'))
            <small class="field-error">{{ $bag->first('description') }}</small>
        @endif
    </label>

    <label class="toggle-field">
        <input type="checkbox" name="is_system" value="1" @checked($isSystem)>
        <span>Modulo del sistema</span>
    </label>

    <fieldset class="form-section">
        <legend>Acciones disponibles</legend>

        <div class="choice-grid">
            @forelse ($actionOptions as $action)
                <label class="choice-card">
                    <input
                        type="checkbox"
                        name="action_ids[]"
                        value="{{ $action['id'] }}"
                        @checked(in_array($action['id'], $selectedActionIds, true))
                    >

                    <span class="choice-card__copy">
                        <strong>{{ $action['name'] }}</strong>
                        <small>{{ $action['slug'] }} @if (filled($action['description'])) · {{ $action['description'] }} @endif</small>
                    </span>
                </label>
            @empty
                <p class="empty-state">
                    Aun no hay acciones registradas en el catalogo base.
                </p>
            @endforelse
        </div>

        @if ($bag->has('action_ids') || $bag->has('action_ids.*'))
            <small class="field-error">{{ $bag->first('action_ids') ?: $bag->first('action_ids.*') }}</small>
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
