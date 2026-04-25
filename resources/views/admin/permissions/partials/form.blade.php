@php
    $bag = $errors->getBag($errorBag);
    $defaults = $permission ?? [];
    $reopened = session('modal') === $modalId;
    $nameValue = $reopened ? old('name', $defaults['name'] ?? '') : ($defaults['name'] ?? '');
    $moduleValue = (int) ($reopened ? old('module_id', $defaults['module_id'] ?? 0) : ($defaults['module_id'] ?? 0));
    $actionValue = (int) ($reopened ? old('permission_action_id', $defaults['action_id'] ?? 0) : ($defaults['action_id'] ?? 0));
    $descriptionValue = $reopened ? old('description', $defaults['description'] ?? '') : ($defaults['description'] ?? '');
    $selectedModule = collect($moduleOptions)->first(static fn (array $module): bool => (int) $module['id'] === $moduleValue);
    $selectedAction = collect($actionOptions)->first(static fn (array $action): bool => (int) $action['id'] === $actionValue);
    $previewValue = filled($selectedModule['slug'] ?? null) && filled($selectedAction['slug'] ?? null)
        ? $selectedModule['slug'].'.'.$selectedAction['slug']
        : 'module.action';
@endphp

<form class="modal-form" method="POST" action="{{ $action }}">
    @csrf

    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Nombre del permiso</span>
            <input type="text" name="name" value="{{ $nameValue }}" required data-modal-focus>

            @if ($bag->has('name'))
                <small class="field-error">{{ $bag->first('name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Modulo</span>
            <select name="module_id" required data-permission-module>
                <option value="">Selecciona un modulo</option>
                @foreach ($moduleOptions as $module)
                    <option
                        value="{{ $module['id'] }}"
                        data-slug="{{ $module['slug'] }}"
                        data-actions="{{ implode(',', $module['action_ids']) }}"
                        @selected($moduleValue === $module['id'])
                    >
                        {{ $module['name'] }}
                    </option>
                @endforeach
            </select>

            @if ($bag->has('module_id'))
                <small class="field-error">{{ $bag->first('module_id') }}</small>
            @endif
        </label>
    </div>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Accion</span>
            <select name="permission_action_id" required data-permission-action>
                <option value="">Selecciona una accion</option>
                @foreach ($actionOptions as $action)
                    <option
                        value="{{ $action['id'] }}"
                        data-slug="{{ $action['slug'] }}"
                        @selected($actionValue === $action['id'])
                    >
                        {{ $action['name'] }} · {{ $action['slug'] }}
                    </option>
                @endforeach
            </select>

            @if ($bag->has('permission_action_id'))
                <small class="field-error">{{ $bag->first('permission_action_id') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Slug resultante</span>
            <div class="preview-box">
                <code data-permission-slug-preview>{{ $previewValue }}</code>
            </div>
            <small class="form-help">Se genera automaticamente con el formato modulo.accion a partir de los selects.</small>
        </label>
    </div>

    <label class="form-field">
        <span>Descripcion</span>
        <textarea name="description" rows="3" placeholder="Aclara para que sirve este permiso.">{{ $descriptionValue }}</textarea>

        @if ($bag->has('description'))
            <small class="field-error">{{ $bag->first('description') }}</small>
        @endif
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
