@php
    $bag = $errors->getBag($errorBag);
    $defaults = $currency ?? [];
    $reopened = session('modal') === $modalId;
    $defaultActive = array_key_exists('is_active', $defaults) ? ! empty($defaults['is_active']) : true;
    $nameValue = $reopened ? old('name', $defaults['name'] ?? '') : ($defaults['name'] ?? '');
    $codeValue = $reopened ? old('code', $defaults['code'] ?? '') : ($defaults['code'] ?? '');
    $symbolValue = $reopened ? old('symbol', $defaults['symbol'] ?? '') : ($defaults['symbol'] ?? '');
    $isActiveRaw = $reopened ? old('is_active', $defaultActive ? '1' : null) : ($defaultActive ? '1' : null);
    $isActive = in_array($isActiveRaw, [true, 1, '1', 'on'], true);
@endphp

<form class="modal-form" method="POST" action="{{ $action }}">
    @csrf

    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Nombre</span>
            <input type="text" name="name" value="{{ $nameValue }}" required data-modal-focus placeholder="Peso colombiano">

            @if ($bag->has('name'))
                <small class="field-error">{{ $bag->first('name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Codigo</span>
            <input type="text" name="code" value="{{ $codeValue }}" required maxlength="10" placeholder="COP">
            <small class="form-help">Se guarda en mayusculas para mantener consistencia.</small>

            @if ($bag->has('code'))
                <small class="field-error">{{ $bag->first('code') }}</small>
            @endif
        </label>
    </div>

    <label class="form-field">
        <span>Simbolo</span>
        <input type="text" name="symbol" value="{{ $symbolValue }}" maxlength="10" placeholder="$">

        @if ($bag->has('symbol'))
            <small class="field-error">{{ $bag->first('symbol') }}</small>
        @endif
    </label>

    <label class="toggle-field">
        <input type="checkbox" name="is_active" value="1" @checked($isActive)>
        <span>Moneda disponible para nuevos precios</span>
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
