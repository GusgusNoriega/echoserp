@extends('layouts.admin')

@section('content')
    @php
        $numberValue = old('number', $quotation['number'] ?? '');
        $statusValue = old('status', $quotation['status'] ?? 'draft');
        $issueDateValue = old('issue_date', $quotation['issue_date'] ?? now()->toDateString());
        $validUntilValue = old('valid_until', $quotation['valid_until'] ?? '');
        $titleValue = old('title', $quotation['title'] ?? '');
        $summaryValue = old('summary', $quotation['summary'] ?? '');
        $customerValue = (int) old('customer_id', $quotation['customer_id'] ?? 0);
        $clientCompanyValue = old('client_company_name', $quotation['client_company_name'] ?? '');
        $clientDocumentLabelValue = old('client_document_label', $quotation['client_document_label'] ?? 'RUC');
        $clientDocumentNumberValue = old('client_document_number', $quotation['client_document_number'] ?? '');
        $clientEmailValue = old('client_email', $quotation['client_email'] ?? '');
        $clientPhoneValue = old('client_phone', $quotation['client_phone'] ?? '');
        $clientAddressValue = old('client_address', $quotation['client_address'] ?? '');
        $currencyValue = (int) old('currency_id', $quotation['currency_id'] ?? 0);
        $salesAdvisorValue = (int) old('sales_advisor_id', $quotation['sales_advisor_id'] ?? 0);
        $workStartValue = old('work_start_date', $quotation['work_start_date'] ?? '');
        $workEndValue = old('work_end_date', $quotation['work_end_date'] ?? '');
        $isEventValue = filter_var(old('is_event', $quotation['is_event'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $eventDateValues = old('event_dates', $quotation['event_dates'] ?? ['']);
        $eventSetupValue = old('event_setup', $quotation['event_setup'] ?? '');
        $eventTeardownValue = old('event_teardown', $quotation['event_teardown'] ?? '');
        $eventLocationValue = old('event_location', $quotation['event_location'] ?? '');
        $taxRateValue = old('tax_rate', $quotation['tax_rate'] ?? '0.00');
        $notesValue = old('notes', $quotation['notes'] ?? '');
        $termsValue = old('terms_and_conditions', $quotation['terms_and_conditions'] ?? '');

        $lineItems = old('line_items', $quotation['line_items'] ?? []);

        if (! is_array($eventDateValues)) {
            $eventDateValues = [$eventDateValues];
        }

        $eventDateValues = collect($eventDateValues)
            ->map(static fn (mixed $date): string => trim((string) $date))
            ->values()
            ->all();

        if ($eventDateValues === []) {
            $eventDateValues = [''];
        }

        if (! is_array($lineItems) || $lineItems === []) {
            $lineItems = [[
                'quotation_item_id' => '',
                'catalog_lookup' => '',
                'is_multiple' => false,
                'name' => '',
                'description' => '',
                'specifications_text' => '',
                'image_path' => '',
                'image_source' => '',
                'image_url' => '',
                'remove_image' => '0',
                'quantity' => '1',
                'unit_label' => '',
                'unit_price' => '',
                'discount_amount' => '0.00',
                'sub_items' => [],
            ]];
        }

        $summarySubtotal = collect($lineItems)->sum(static function (array $item): float {
            $subItemsTotal = collect($item['sub_items'] ?? [])->sum(static fn (mixed $subItem): float => is_array($subItem) ? (float) ($subItem['price'] ?? 0) : 0);
            $unitPrice = filter_var($item['is_multiple'] ?? false, FILTER_VALIDATE_BOOLEAN) && $subItemsTotal > 0
                ? $subItemsTotal
                : (float) ($item['unit_price'] ?? 0);

            return ((float) ($item['quantity'] ?? 0)) * $unitPrice;
        });
        $summaryDiscount = collect($lineItems)->sum(static fn (array $item): float => (float) ($item['discount_amount'] ?? 0));
        $summaryBase = max($summarySubtotal - $summaryDiscount, 0);
        $summaryTax = round($summaryBase * (((float) $taxRateValue) / 100), 2);
        $summaryTotal = round($summaryBase + $summaryTax, 2);
    @endphp

    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Editor comercial</p>
            <h2>Estructura la cotizacion completa y deja lista la data para un PDF detallado.</h2>
            <p class="section-copy">
                Los datos del cliente pueden salir del area de clientes o registrarse manualmente, mientras que los items
                pueden salir del catalogo o crearse manualmente dentro del documento.
            </p>

            <div class="hero-actions">
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.index') }}">
                    Volver al listado
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.catalog.index') }}">
                    Abrir catalogo
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.settings.index') }}">
                    Configuracion
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">Numeracion</span>
                <span class="pill">Resumen del proyecto</span>
                <span class="pill">Items valorizados</span>
                <span class="pill">Fechas del proyecto</span>
                <span class="pill">Datos de evento</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Snapshot del emisor</p>
                    <h3>Datos que heredara la cotizacion</h3>
                </div>
            </div>

            <ul class="stack-list">
                <li>
                    <div>
                        <strong>{{ $settingsPreview['company_name'] ?: 'Sin empresa configurada' }}</strong>
                        <small>{{ $settingsPreview['company_document_label'] }} {{ $settingsPreview['company_document_number'] ?: 'pendiente' }}</small>
                    </div>
                </li>
                <li>
                    <div>
                        <strong>{{ $settingsPreview['company_email'] ?: 'Sin correo' }}</strong>
                        <small>correo del emisor</small>
                    </div>
                </li>
                <li>
                    <div>
                        <strong>{{ $settingsPreview['company_phone'] ?: 'Sin telefono' }}</strong>
                        <small>telefono corporativo</small>
                    </div>
                </li>
                <li>
                    <div>
                        <strong>{{ $settingsPreview['default_signer_name'] ?: 'Sin firmante' }}</strong>
                        <small>{{ $settingsPreview['default_signer_title'] ?: 'cargo no definido' }}</small>
                    </div>
                </li>
            </ul>
        </aside>
    </section>

    <form
        class="quote-editor"
        method="POST"
        action="{{ $formAction }}"
        enctype="multipart/form-data"
        data-quotation-editor
        data-catalog-items='@json($catalogItems)'
        data-customers='@json($customerOptions)'
    >
        @csrf

        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif

        <div class="content-grid quote-editor-layout">
            <div class="quote-editor-main">
                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Datos generales</p>
                            <h3>Cabecera de la cotizacion</h3>
                        </div>
                    </div>

                    <div class="form-grid form-grid--quote-head">
                        <label class="form-field">
                            <span>Numero</span>
                            <input type="text" name="number" value="{{ $numberValue }}" placeholder="Si lo dejas vacio se genera automaticamente">

                            @if ($errors->has('number'))
                                <small class="field-error">{{ $errors->first('number') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Estado</span>
                            <select name="status">
                                @foreach ($statusOptions as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" @selected($statusValue === $statusKey)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>

                            @if ($errors->has('status'))
                                <small class="field-error">{{ $errors->first('status') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Fecha de emision</span>
                            <input type="date" name="issue_date" value="{{ $issueDateValue }}" required>

                            @if ($errors->has('issue_date'))
                                <small class="field-error">{{ $errors->first('issue_date') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Valida hasta</span>
                            <input type="date" name="valid_until" value="{{ $validUntilValue }}">

                            @if ($errors->has('valid_until'))
                                <small class="field-error">{{ $errors->first('valid_until') }}</small>
                            @endif
                        </label>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Asunto o nombre de la cotizacion</span>
                            <input type="text" name="title" value="{{ $titleValue }}" required data-modal-focus placeholder="CRM integral para eventos - Empresa SAC">

                            @if ($errors->has('title'))
                                <small class="field-error">{{ $errors->first('title') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Moneda del documento</span>
                            <select name="currency_id">
                                <option value="">Selecciona una moneda</option>
                                @foreach ($currencyOptions as $currency)
                                    <option value="{{ $currency['id'] }}" @selected($currencyValue === $currency['id'])>
                                        {{ $currency['label'] }} @if (! $currency['is_active']) - Inactiva @endif
                                    </option>
                                @endforeach
                            </select>

                            @if ($errors->has('currency_id'))
                                <small class="field-error">{{ $errors->first('currency_id') }}</small>
                            @endif
                        </label>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Asesor de ventas</span>
                            <select name="sales_advisor_id">
                                <option value="">Sin asesor asignado</option>
                                @foreach ($userOptions as $user)
                                    <option value="{{ $user['id'] }}" @selected($salesAdvisorValue === (int) $user['id'])>
                                        {{ $user['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($errors->has('sales_advisor_id'))
                                <small class="field-error">{{ $errors->first('sales_advisor_id') }}</small>
                            @endif
                        </label>
                    </div>

                    <label class="form-field">
                        <span>Resumen o descripcion general</span>
                        <textarea name="summary" rows="5" placeholder="Describe el alcance general de la cotizacion">{{ $summaryValue }}</textarea>

                        @if ($errors->has('summary'))
                            <small class="field-error">{{ $errors->first('summary') }}</small>
                        @endif
                    </label>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Cliente</p>
                            <h3>Datos del cliente</h3>
                        </div>
                    </div>

                    <label class="form-field">
                        <span>Cliente registrado</span>
                        <select name="customer_id" data-customer-select>
                            <option value="">Cliente manual / sin registro</option>
                            @foreach ($customerOptions as $customer)
                                <option value="{{ $customer['id'] }}" @selected($customerValue === (int) $customer['id'])>
                                    {{ $customer['label'] }} @if (! $customer['is_active']) - Inactivo @endif
                                </option>
                            @endforeach
                        </select>

                        @if ($errors->has('customer_id'))
                            <small class="field-error">{{ $errors->first('customer_id') }}</small>
                        @endif
                    </label>

                    <div class="form-grid form-grid--quote-head">
                        <label class="form-field">
                            <span>Razon social</span>
                            <input type="text" name="client_company_name" value="{{ $clientCompanyValue }}" required placeholder="Empresa cliente" data-customer-company>

                            @if ($errors->has('client_company_name'))
                                <small class="field-error">{{ $errors->first('client_company_name') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Tipo de documento</span>
                            <input type="text" name="client_document_label" value="{{ $clientDocumentLabelValue }}" required placeholder="RUC" data-customer-document-label>

                            @if ($errors->has('client_document_label'))
                                <small class="field-error">{{ $errors->first('client_document_label') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Numero de documento</span>
                            <input type="text" name="client_document_number" value="{{ $clientDocumentNumberValue }}" placeholder="20508768533" data-customer-document-number>

                            @if ($errors->has('client_document_number'))
                                <small class="field-error">{{ $errors->first('client_document_number') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Correo</span>
                            <input type="email" name="client_email" value="{{ $clientEmailValue }}" placeholder="cliente@empresa.com" data-customer-email>

                            @if ($errors->has('client_email'))
                                <small class="field-error">{{ $errors->first('client_email') }}</small>
                            @endif
                        </label>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Telefono</span>
                            <input type="text" name="client_phone" value="{{ $clientPhoneValue }}" placeholder="+51 999 999 999" data-customer-phone>

                            @if ($errors->has('client_phone'))
                                <small class="field-error">{{ $errors->first('client_phone') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Direccion</span>
                            <input type="text" name="client_address" value="{{ $clientAddressValue }}" placeholder="Direccion fiscal o comercial" data-customer-address>

                            @if ($errors->has('client_address'))
                                <small class="field-error">{{ $errors->first('client_address') }}</small>
                            @endif
                        </label>
                    </div>

                    <input type="hidden" name="is_event" value="0">
                    <label class="toggle-field">
                        <input type="checkbox" name="is_event" value="1" data-event-toggle @checked($isEventValue)>
                        <span>Esta cotizacion es para un evento</span>
                    </label>

                    <div data-event-fields @if (! $isEventValue) hidden @endif>
                        <div class="form-grid form-grid--two">
                            <div class="form-field">
                                <span>Fecha de evento</span>
                                <div class="quote-date-list" data-event-date-list data-next-event-date-index="{{ count($eventDateValues) }}">
                                    @foreach ($eventDateValues as $eventDateIndex => $eventDateValue)
                                        <div class="form-grid form-grid--two" data-event-date-row>
                                            <label class="form-field">
                                                <span data-event-date-label>Fecha {{ $eventDateIndex + 1 }}</span>
                                                <input type="date" name="event_dates[{{ $eventDateIndex }}]" value="{{ $eventDateValue }}">

                                                @if ($errors->has("event_dates.$eventDateIndex"))
                                                    <small class="field-error">{{ $errors->first("event_dates.$eventDateIndex") }}</small>
                                                @endif
                                            </label>

                                            <div class="quote-task-card__actions">
                                                <button class="button-link button-link--ghost button-link--compact" type="button" data-remove-event-date>
                                                    Quitar fecha
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="hero-actions">
                                    <button class="button-link button-link--ghost button-link--compact" type="button" data-add-event-date>
                                        Agregar fecha
                                    </button>
                                </div>

                                @if ($errors->has('event_dates'))
                                    <small class="field-error">{{ $errors->first('event_dates') }}</small>
                                @endif
                            </div>

                            <label class="form-field">
                                <span>Lugar</span>
                                <input type="text" name="event_location" value="{{ $eventLocationValue }}" placeholder="San Martin Porres">

                                @if ($errors->has('event_location'))
                                    <small class="field-error">{{ $errors->first('event_location') }}</small>
                                @endif
                            </label>
                        </div>

                        <div class="form-grid form-grid--two">
                            <label class="form-field">
                                <span>Montaje</span>
                                <input type="date" name="event_setup" value="{{ $eventSetupValue }}">

                                @if ($errors->has('event_setup'))
                                    <small class="field-error">{{ $errors->first('event_setup') }}</small>
                                @endif
                            </label>

                            <label class="form-field">
                                <span>Desmontaje</span>
                                <input type="date" name="event_teardown" value="{{ $eventTeardownValue }}">

                                @if ($errors->has('event_teardown'))
                                    <small class="field-error">{{ $errors->first('event_teardown') }}</small>
                                @endif
                            </label>
                        </div>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Fechas del proyecto</p>
                            <h3>Cronograma general</h3>
                        </div>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Fecha de inicio</span>
                            <input type="date" name="work_start_date" value="{{ $workStartValue }}">

                            @if ($errors->has('work_start_date'))
                                <small class="field-error">{{ $errors->first('work_start_date') }}</small>
                            @endif
                        </label>

                        <label class="form-field">
                            <span>Fecha de finalizacion (opcional)</span>
                            <input type="date" name="work_end_date" value="{{ $workEndValue }}">

                            @if ($errors->has('work_end_date'))
                                <small class="field-error">{{ $errors->first('work_end_date') }}</small>
                            @endif
                        </label>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Detalle economico</p>
                            <h3>Items de la cotizacion</h3>
                        </div>

                        <button class="button-link button-link--ghost button-link--compact" type="button" data-add-line-item>
                            Agregar item
                        </button>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Tasa de impuesto (%)</span>
                            <input type="number" name="tax_rate" value="{{ $taxRateValue }}" min="0" max="100" step="0.01" placeholder="0.00" data-tax-rate>

                            @if ($errors->has('tax_rate'))
                                <small class="field-error">{{ $errors->first('tax_rate') }}</small>
                            @endif
                        </label>
                    </div>

                    @if ($errors->has('line_items'))
                        <p class="field-error">{{ $errors->first('line_items') }}</p>
                    @endif

                    <div class="quote-line-list" data-line-item-list data-next-line-index="{{ count($lineItems) }}">
                        @foreach ($lineItems as $index => $lineItem)
                            @include('admin.quotations.partials.quotation-line-item', [
                                'index' => $index,
                                'lineItem' => $lineItem,
                            ])
                        @endforeach
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Notas y terminos</p>
                            <h3>Condiciones comerciales</h3>
                        </div>
                    </div>

                    <label class="form-field">
                        <span>Notas</span>
                        <textarea name="notes" rows="6" placeholder="Aclaraciones, requisitos o entregables">{{ $notesValue }}</textarea>

                        @if ($errors->has('notes'))
                            <small class="field-error">{{ $errors->first('notes') }}</small>
                        @endif
                    </label>

                    <label class="form-field">
                        <span>Terminos y condiciones</span>
                        <textarea name="terms_and_conditions" rows="8" placeholder="Moneda, forma de pago, vigencia y condiciones especiales">{{ $termsValue }}</textarea>

                        @if ($errors->has('terms_and_conditions'))
                            <small class="field-error">{{ $errors->first('terms_and_conditions') }}</small>
                        @endif
                    </label>
                </article>

                <div class="hero-actions">
                    <a class="button-link button-link--ghost" href="{{ route('admin.quotations.index') }}">
                        Cancelar
                    </a>
                    <button class="button-link button-link--primary" type="submit">
                        {{ $submitLabel }}
                    </button>
                </div>
            </div>

            <aside class="quote-editor-side">
                <article class="panel-card quote-summary-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Resumen</p>
                            <h3>Totales de la cotizacion</h3>
                        </div>
                    </div>

                    <div class="quote-summary-list">
                        <div>
                            <span>Subtotal</span>
                            <strong data-summary-subtotal>{{ number_format($summarySubtotal, 2, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span>Descuento</span>
                            <strong data-summary-discount>{{ number_format($summaryDiscount, 2, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span>Impuesto</span>
                            <strong data-summary-tax>{{ number_format($summaryTax, 2, ',', '.') }}</strong>
                        </div>
                        <div class="is-total">
                            <span>Total</span>
                            <strong data-summary-total>{{ number_format($summaryTotal, 2, ',', '.') }}</strong>
                        </div>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Ayuda rapida</p>
                            <h3>Como usar el editor</h3>
                        </div>
                    </div>

                    <ul class="bullet-list bullet-list--muted">
                        <li>Puedes elegir un cliente registrado o dejar la cotizacion como cliente manual.</li>
                        <li>Al elegir un cliente, sus datos actuales se copian a los campos editables de la cotizacion.</li>
                        <li>Si eliges un item del catalogo, se completan nombre, descripcion, unidad, precio e imagen.</li>
                        <li>Puedes editar cualquier campo autocompletado antes de guardar.</li>
                        <li>Cada linea puede mantener la imagen del catalogo o reemplazarse con una imagen solo para esa cotizacion.</li>
                    </ul>
                </article>
            </aside>
        </div>

        <datalist id="quotation-catalog-options">
            @foreach ($catalogItems as $catalogItem)
                <option value="{{ $catalogItem['lookup_label'] }}">{{ $catalogItem['name'] }}</option>
            @endforeach
        </datalist>

        <template data-line-item-template>
            @include('admin.quotations.partials.quotation-line-item', [
                'index' => '__INDEX__',
                'lineItem' => [
                'quotation_item_id' => '',
                'catalog_lookup' => '',
                'is_multiple' => false,
                'name' => '',
                'description' => '',
                'specifications_text' => '',
                'image_path' => '',
                'image_source' => '',
                'image_url' => '',
                'remove_image' => '0',
                'quantity' => '1',
                'unit_label' => '',
                'unit_price' => '',
                'discount_amount' => '0.00',
                'sub_items' => [],
                ],
            ])
        </template>

        <template data-event-date-template>
            <div class="form-grid form-grid--two" data-event-date-row>
                <label class="form-field">
                    <span data-event-date-label>Fecha __EVENT_DATE_NUMBER__</span>
                    <input type="date" name="event_dates[__EVENT_DATE_INDEX__]" value="">
                </label>

                <div class="quote-task-card__actions">
                    <button class="button-link button-link--ghost button-link--compact" type="button" data-remove-event-date>
                        Quitar fecha
                    </button>
                </div>
            </div>
        </template>
    </form>
@endsection
