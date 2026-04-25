@php
    $bag = $errors->getBag($errorBag);
    $defaults = $customer ?? [];
    $reopened = session('modal') === $modalId;
    $defaultActive = array_key_exists('is_active', $defaults) ? ! empty($defaults['is_active']) : true;
    $companyValue = $reopened ? old('company_name', $defaults['company_name'] ?? '') : ($defaults['company_name'] ?? '');
    $documentLabelValue = $reopened ? old('document_label', $defaults['document_label'] ?? 'RUC') : ($defaults['document_label'] ?? 'RUC');
    $documentNumberValue = $reopened ? old('document_number', $defaults['document_number'] ?? '') : ($defaults['document_number'] ?? '');
    $contactValue = $reopened ? old('contact_name', $defaults['contact_name'] ?? '') : ($defaults['contact_name'] ?? '');
    $emailValue = $reopened ? old('email', $defaults['email'] ?? '') : ($defaults['email'] ?? '');
    $phoneValue = $reopened ? old('phone', $defaults['phone'] ?? '') : ($defaults['phone'] ?? '');
    $addressValue = $reopened ? old('address', $defaults['address'] ?? '') : ($defaults['address'] ?? '');
    $notesValue = $reopened ? old('notes', $defaults['notes'] ?? '') : ($defaults['notes'] ?? '');
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
            <span>Razon social</span>
            <input type="text" name="company_name" value="{{ $companyValue }}" required data-modal-focus placeholder="Empresa cliente">

            @if ($bag->has('company_name'))
                <small class="field-error">{{ $bag->first('company_name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Contacto</span>
            <input type="text" name="contact_name" value="{{ $contactValue }}" placeholder="Nombre de contacto">

            @if ($bag->has('contact_name'))
                <small class="field-error">{{ $bag->first('contact_name') }}</small>
            @endif
        </label>
    </div>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Tipo de documento</span>
            <input type="text" name="document_label" value="{{ $documentLabelValue }}" required maxlength="50" placeholder="RUC">

            @if ($bag->has('document_label'))
                <small class="field-error">{{ $bag->first('document_label') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Numero de documento</span>
            <input type="text" name="document_number" value="{{ $documentNumberValue }}" maxlength="50" placeholder="20508768533">

            @if ($bag->has('document_number'))
                <small class="field-error">{{ $bag->first('document_number') }}</small>
            @endif
        </label>
    </div>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Correo</span>
            <input type="email" name="email" value="{{ $emailValue }}" placeholder="cliente@empresa.com">

            @if ($bag->has('email'))
                <small class="field-error">{{ $bag->first('email') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Telefono</span>
            <input type="text" name="phone" value="{{ $phoneValue }}" maxlength="50" placeholder="+51 999 999 999">

            @if ($bag->has('phone'))
                <small class="field-error">{{ $bag->first('phone') }}</small>
            @endif
        </label>
    </div>

    <label class="form-field">
        <span>Direccion</span>
        <input type="text" name="address" value="{{ $addressValue }}" maxlength="500" placeholder="Direccion fiscal o comercial">

        @if ($bag->has('address'))
            <small class="field-error">{{ $bag->first('address') }}</small>
        @endif
    </label>

    <label class="form-field">
        <span>Notas internas</span>
        <textarea name="notes" rows="5" placeholder="Condiciones, referencias o informacion interna del cliente">{{ $notesValue }}</textarea>

        @if ($bag->has('notes'))
            <small class="field-error">{{ $bag->first('notes') }}</small>
        @endif
    </label>

    <label class="toggle-field">
        <input type="checkbox" name="is_active" value="1" @checked($isActive)>
        <span>Cliente disponible para nuevas cotizaciones</span>
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
