@php
    $lineItem = $lineItem ?? [];
    $integerValue = static function (mixed $value): string {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;

        if (! is_finite($number)) {
            return '';
        }

        return (string) max((int) round($number), 0);
    };
    $quantityValue = $integerValue($lineItem['quantity'] ?? '1');
    $unitPriceValue = $lineItem['unit_price'] ?? '';
    $discountValue = $lineItem['discount_amount'] ?? '0.00';
    $lineTotal = max(((float) $quantityValue * (float) ($unitPriceValue ?: 0)) - (float) ($discountValue ?: 0), 0);
    $imagePath = $lineItem['image_path'] ?? '';
    $imageSource = $lineItem['image_source'] ?? '';
    $imageUrl = $lineItem['image_url'] ?? (filled($imagePath)
        ? \Illuminate\Support\Facades\Storage::disk('quote_media')->url($imagePath)
        : '');
    $imageCaption = match ($imageSource) {
        'catalog' => 'Imagen heredada del catalogo comercial.',
        'uploaded' => 'Imagen cargada manualmente para esta cotizacion.',
        default => 'Aun no se ha definido una imagen para este item.',
    };
@endphp

<article class="quote-line-card" data-line-item>
    <div class="quote-line-card__header">
        <div>
            <p class="section-kicker">Item</p>
            <h4>Linea comercial</h4>
        </div>

        <button class="button-link button-link--ghost button-link--compact" type="button" data-remove-line-item>
            Quitar item
        </button>
    </div>

    <input type="hidden" name="line_items[{{ $index }}][quotation_item_id]" value="{{ $lineItem['quotation_item_id'] ?? '' }}" data-catalog-id>
    <input type="hidden" name="line_items[{{ $index }}][image_path]" value="{{ $imagePath }}" data-line-image-path>
    <input type="hidden" name="line_items[{{ $index }}][image_source]" value="{{ $imageSource }}" data-line-image-source>
    <input type="hidden" name="line_items[{{ $index }}][image_url]" value="{{ $imageUrl }}" data-line-image-url>
    <input type="hidden" name="line_items[{{ $index }}][remove_image]" value="{{ $lineItem['remove_image'] ?? '0' }}" data-line-image-remove>
    <textarea hidden name="line_items[{{ $index }}][specifications_text]" data-line-specifications>{{ $lineItem['specifications_text'] ?? '' }}</textarea>

    <div class="form-grid form-grid--quote-line">
        <label class="form-field">
            <span>Autocompletar desde catalogo</span>
            <input
                type="text"
                name="line_items[{{ $index }}][catalog_lookup]"
                value="{{ $lineItem['catalog_lookup'] ?? '' }}"
                list="quotation-catalog-options"
                placeholder="#12 - Servicio - Nombre del item"
                data-catalog-lookup
            >
            <small class="form-help">Puedes buscar un producto o servicio y luego ajustar los datos manualmente si hace falta.</small>
        </label>

        <label class="form-field">
            <span>Nombre del item</span>
            <input type="text" name="line_items[{{ $index }}][name]" value="{{ $lineItem['name'] ?? '' }}" required data-line-name>

            @if ($errors->has("line_items.$index.name"))
                <small class="field-error">{{ $errors->first("line_items.$index.name") }}</small>
            @endif
        </label>
    </div>

    <label class="form-field">
        <span>Descripcion comercial</span>
        <textarea name="line_items[{{ $index }}][description]" rows="3" placeholder="Detalle que saldra en la cotizacion" data-line-description>{{ $lineItem['description'] ?? '' }}</textarea>

        @if ($errors->has("line_items.$index.description"))
            <small class="field-error">{{ $errors->first("line_items.$index.description") }}</small>
        @endif
    </label>

    <div class="form-grid form-grid--quote-line-media">
        <label class="form-field">
            <span>Imagen del item</span>
            <input type="file" name="line_items[{{ $index }}][image]" accept="image/*" data-line-image-input>
            <small class="form-help">Si el item viene del catalogo con imagen, se replica automaticamente. Puedes reemplazarla solo para esta cotizacion.</small>

            @if ($errors->has("line_items.$index.image"))
                <small class="field-error">{{ $errors->first("line_items.$index.image") }}</small>
            @endif
        </label>

        <div class="quote-line-media">
            <div class="media-preview" data-line-image-preview @if (! $imageUrl) hidden @endif>
                <img src="{{ $imageUrl }}" alt="Imagen del item" data-line-image-preview-img>
                <small class="media-preview__caption" data-line-image-caption>{{ $imageCaption }}</small>
            </div>

            <div class="quotation-card__placeholder quote-line-media__placeholder" data-line-image-placeholder @if ($imageUrl) hidden @endif>
                <strong>Sin imagen</strong>
                <span>Selecciona una foto manual o usa un item del catalogo con imagen.</span>
            </div>

            <button
                class="button-link button-link--ghost button-link--compact"
                type="button"
                data-clear-line-image
                @if (! $imageUrl) hidden @endif
            >
                Quitar imagen
            </button>
        </div>
    </div>

    <div class="form-grid form-grid--quote-metrics">
        <label class="form-field">
            <span>Cantidad</span>
            <input type="number" name="line_items[{{ $index }}][quantity]" value="{{ $quantityValue }}" min="1" step="1" inputmode="numeric" required data-line-quantity data-whole-number>

            @if ($errors->has("line_items.$index.quantity"))
                <small class="field-error">{{ $errors->first("line_items.$index.quantity") }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Unidad</span>
            <input type="text" name="line_items[{{ $index }}][unit_label]" value="{{ $lineItem['unit_label'] ?? '' }}" placeholder="unidad, modulo, fase..." data-line-unit>

            @if ($errors->has("line_items.$index.unit_label"))
                <small class="field-error">{{ $errors->first("line_items.$index.unit_label") }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>P. unitario</span>
            <input type="number" name="line_items[{{ $index }}][unit_price]" value="{{ $unitPriceValue }}" min="0" step="0.01" placeholder="0.00" data-line-unit-price>

            @if ($errors->has("line_items.$index.unit_price"))
                <small class="field-error">{{ $errors->first("line_items.$index.unit_price") }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Descuento</span>
            <input type="number" name="line_items[{{ $index }}][discount_amount]" value="{{ $discountValue }}" min="0" step="0.01" placeholder="0.00" data-line-discount>

            @if ($errors->has("line_items.$index.discount_amount"))
                <small class="field-error">{{ $errors->first("line_items.$index.discount_amount") }}</small>
            @endif
        </label>
    </div>

    <div class="quote-line-card__footer">
        <div class="quote-inline-metric">
            <strong data-line-total>{{ number_format($lineTotal, 2, ',', '.') }}</strong>
            <span>Total de linea</span>
        </div>
    </div>
</article>
