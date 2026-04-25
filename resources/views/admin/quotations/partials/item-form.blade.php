@php
    $bag = $errors->getBag($errorBag);
    $defaults = $item ?? [];
    $reopened = session('modal') === $modalId;
    $defaultActive = array_key_exists('is_active', $defaults) ? ! empty($defaults['is_active']) : true;
    $typeValue = $reopened ? old('type', $defaults['type'] ?? 'product') : ($defaults['type'] ?? 'product');
    $nameValue = $reopened ? old('name', $defaults['name'] ?? '') : ($defaults['name'] ?? '');
    $descriptionValue = $reopened ? old('description', $defaults['description'] ?? '') : ($defaults['description'] ?? '');
    $unitValue = $reopened ? old('unit_label', $defaults['unit_label'] ?? '') : ($defaults['unit_label'] ?? '');
    $specificationsValue = $reopened ? old('specifications_text', $defaults['specifications_text'] ?? '') : ($defaults['specifications_text'] ?? '');
    $priceValue = $reopened ? old('price', $defaults['price'] ?? '') : ($defaults['price'] ?? '');
    $currencyValue = (int) ($reopened ? old('currency_id', $defaults['currency_id'] ?? 0) : ($defaults['currency_id'] ?? 0));
    $isActiveRaw = $reopened ? old('is_active', $defaultActive ? '1' : null) : ($defaultActive ? '1' : null);
    $isActive = in_array($isActiveRaw, [true, 1, '1', 'on'], true);
    $removeImageRaw = $reopened ? old('remove_image') : null;
    $removeImage = in_array($removeImageRaw, [true, 1, '1', 'on'], true);
@endphp

<form class="modal-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf

    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <fieldset class="form-section">
        <legend>Tipo de item</legend>

        <div class="choice-grid">
            <label class="choice-card">
                <input type="radio" name="type" value="product" @checked($typeValue === 'product') data-modal-focus>

                <span class="choice-card__copy">
                    <strong>Producto</strong>
                    <small>Item fisico o tangible del catalogo.</small>
                </span>
            </label>

            <label class="choice-card">
                <input type="radio" name="type" value="service" @checked($typeValue === 'service')>

                <span class="choice-card__copy">
                    <strong>Servicio</strong>
                    <small>Oferta intangible con alcance comercial.</small>
                </span>
            </label>
        </div>

        @if ($bag->has('type'))
            <small class="field-error">{{ $bag->first('type') }}</small>
        @endif
    </fieldset>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Nombre</span>
            <input type="text" name="name" value="{{ $nameValue }}" required>

            @if ($bag->has('name'))
                <small class="field-error">{{ $bag->first('name') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Imagen</span>
            <input type="file" name="image" accept="image/*">
            <small class="form-help">Formatos permitidos: JPG, PNG, WEBP y similares.</small>

            @if ($bag->has('image'))
                <small class="field-error">{{ $bag->first('image') }}</small>
            @endif
        </label>
    </div>

    @if (! empty($defaults['image_url']))
        <div class="media-preview">
            <img src="{{ $defaults['image_url'] }}" alt="Imagen actual de {{ $defaults['name'] ?? 'item' }}">
            <small class="media-preview__caption">Imagen actual registrada en el catalogo.</small>
        </div>
    @endif

    <label class="form-field">
        <span>Descripcion</span>
        <textarea name="description" rows="4" required placeholder="Describe el producto o servicio con enfoque comercial.">{{ $descriptionValue }}</textarea>

        @if ($bag->has('description'))
            <small class="field-error">{{ $bag->first('description') }}</small>
        @endif
    </label>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Unidad sugerida</span>
            <input type="text" name="unit_label" value="{{ $unitValue }}" placeholder="unidad, modulo, hora, fase...">
            <small class="form-help">Se usara para autocompletar la unidad del item dentro de la cotizacion.</small>

            @if ($bag->has('unit_label'))
                <small class="field-error">{{ $bag->first('unit_label') }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Precio</span>
            <input type="number" name="price" value="{{ $priceValue }}" min="0" step="0.01" placeholder="0.00">
            <small class="form-help">Este campo es opcional.</small>

            @if ($bag->has('price'))
                <small class="field-error">{{ $bag->first('price') }}</small>
            @endif
        </label>
    </div>

    <label class="form-field">
        <span>Especificaciones</span>
        <textarea name="specifications_text" rows="5" placeholder="Una especificacion por linea">{{ $specificationsValue }}</textarea>
        <small class="form-help">Cada linea se guarda como una especificacion independiente.</small>

        @if ($bag->has('specifications_text'))
            <small class="field-error">{{ $bag->first('specifications_text') }}</small>
        @endif
    </label>

    <div class="form-grid form-grid--two">
        <label class="form-field">
            <span>Moneda</span>
            <select name="currency_id">
                <option value="">Selecciona una moneda</option>
                @foreach ($currencyOptions as $currency)
                    <option value="{{ $currency['id'] }}" @selected($currencyValue === $currency['id'])>
                        {{ $currency['label'] }} @if (! $currency['is_active']) - Inactiva @endif
                    </option>
                @endforeach
            </select>
            <small class="form-help">Solo es obligatoria si defines un precio.</small>

            @if ($bag->has('currency_id'))
                <small class="field-error">{{ $bag->first('currency_id') }}</small>
            @endif
        </label>
    </div>

    <label class="toggle-field">
        <input type="checkbox" name="is_active" value="1" @checked($isActive)>
        <span>Item disponible para nuevas cotizaciones</span>
    </label>

    @if (! empty($defaults['image_path']))
        <label class="toggle-field">
            <input type="checkbox" name="remove_image" value="1" @checked($removeImage)>
            <span>Eliminar imagen actual si no se carga una nueva</span>
        </label>
    @endif

    <div class="modal-form__footer">
        <button class="button-link button-link--ghost button-link--compact" type="button" data-modal-close>
            Cancelar
        </button>

        <button class="button-link button-link--primary button-link--compact" type="submit">
            {{ $submitLabel }}
        </button>
    </div>
</form>
