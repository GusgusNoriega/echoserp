@extends('layouts.admin')

@section('content')
    @php
        $companyNameValue = old('company_name', $settings['company_name'] ?? '');
        $companyLogoUrl = $settings['company_logo_url'] ?? null;
        $companyLogoPath = $settings['company_logo_path'] ?? null;
        $companyDocumentLabelValue = old('company_document_label', $settings['company_document_label'] ?? 'RUC');
        $companyDocumentNumberValue = old('company_document_number', $settings['company_document_number'] ?? '');
        $companyEmailValue = old('company_email', $settings['company_email'] ?? '');
        $companyPhoneValue = old('company_phone', $settings['company_phone'] ?? '');
        $companyWebsiteValue = old('company_website', $settings['company_website'] ?? '');
        $companyAddressValue = old('company_address', $settings['company_address'] ?? '');
        $numberPrefixValue = old('number_prefix', $settings['number_prefix'] ?? 'COT');
        $defaultValidityDaysValue = old('default_validity_days', $settings['default_validity_days'] ?? 15);
        $defaultTaxRateValue = old('default_tax_rate', $settings['default_tax_rate'] ?? 0);
        $defaultCurrencyValue = (int) old('default_currency_id', $settings['default_currency_id'] ?? 0);
        $defaultNotesValue = old('default_notes', $settings['default_notes'] ?? '');
        $defaultTermsValue = old('default_terms', $settings['default_terms'] ?? '');
        $defaultSignerNameValue = old('default_signer_name', $settings['default_signer_name'] ?? '');
        $defaultSignerTitleValue = old('default_signer_title', $settings['default_signer_title'] ?? '');
    @endphp

    <section class="hero-grid">
        <article class="hero-card hero-card--compact">
            <p class="section-kicker">Defaults del documento</p>
            <h2>Administra los datos corporativos y textos base que heredaran las nuevas cotizaciones.</h2>
            <p class="section-copy">
                Esta configuracion define correo, RUC, direccion, numeracion, vigencia, moneda por defecto, notas y
                terminos comerciales para que el equipo no repita datos en cada documento.
            </p>

            <div class="hero-actions">
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.index') }}">
                    Volver a cotizaciones
                </a>
                <a class="button-link button-link--ghost" href="{{ route('admin.quotations.catalog.index') }}">
                    Ir al catalogo
                </a>
            </div>

            <div class="hero-pills">
                <span class="pill">RUC y direccion</span>
                <span class="pill">Logo PDF</span>
                <span class="pill">Prefijo</span>
                <span class="pill">Moneda base</span>
            </div>
        </article>

        <aside class="preview-card">
            <div class="panel-heading">
                <div>
                    <p class="section-kicker">Indicadores</p>
                    <h3>Resumen rapido</h3>
                </div>
            </div>

            <section class="metrics-grid metrics-grid--three">
                @foreach ($metrics as $metric)
                    <article class="metric-card">
                        <strong>{{ $metric['value'] }}</strong>
                        <span>{{ $metric['label'] }}</span>
                        <small>{{ $metric['detail'] }}</small>
                    </article>
                @endforeach
            </section>
        </aside>
    </section>

    <form method="POST" action="{{ route('admin.quotations.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="content-grid quote-editor-layout">
            <div class="quote-editor-main">
                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Empresa</p>
                            <h3>Datos del emisor</h3>
                        </div>
                    </div>

                    <div class="form-grid form-grid--quote-head">
                        <label class="form-field">
                            <span>Nombre de la empresa</span>
                            <input type="text" name="company_name" value="{{ $companyNameValue }}" placeholder="Echos Peru SAC">
                            @error('company_name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Tipo de documento</span>
                            <input type="text" name="company_document_label" value="{{ $companyDocumentLabelValue }}" required placeholder="RUC">
                            @error('company_document_label')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Numero de documento</span>
                            <input type="text" name="company_document_number" value="{{ $companyDocumentNumberValue }}" placeholder="20508768533">
                            @error('company_document_number')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Correo</span>
                            <input type="email" name="company_email" value="{{ $companyEmailValue }}" placeholder="info@empresa.com">
                            @error('company_email')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Telefono</span>
                            <input type="text" name="company_phone" value="{{ $companyPhoneValue }}" placeholder="+51 999 999 999">
                            @error('company_phone')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Web o dominio</span>
                            <input type="text" name="company_website" value="{{ $companyWebsiteValue }}" placeholder="empresa.com">
                            @error('company_website')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <label class="form-field">
                        <span>Direccion</span>
                        <textarea name="company_address" rows="3" placeholder="Direccion corporativa o fiscal">{{ $companyAddressValue }}</textarea>
                        @error('company_address')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Identidad visual</p>
                            <h3>Logo para cotizaciones PDF</h3>
                        </div>
                    </div>

                    <div class="form-grid form-grid--quote-line-media">
                        <label class="form-field">
                            <span>Imagen del logo</span>
                            <input type="file" name="company_logo" accept="image/*">
                            <small class="form-help">Este logo se usara solo en las cotizaciones PDF. No cambia el logo del panel administrativo.</small>

                            @error('company_logo')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <div class="quote-line-media">
                            @if ($companyLogoUrl)
                                <div class="media-preview">
                                    <img src="{{ $companyLogoUrl }}" alt="Logo actual de cotizaciones">
                                    <small class="media-preview__caption">Logo actual para cotizaciones PDF.</small>
                                </div>

                                <label class="toggle-field">
                                    <input type="checkbox" name="remove_company_logo" value="1">
                                    <span>
                                        <strong>Quitar logo actual</strong>
                                        <small class="form-help">Las nuevas cotizaciones saldran sin logo si no cargas otro archivo.</small>
                                    </span>
                                </label>
                            @else
                                <div class="quotation-card__placeholder quote-line-media__placeholder">
                                    <strong>Sin logo PDF</strong>
                                    <span>Sube una imagen para usarla en la cabecera de las cotizaciones.</span>
                                </div>
                            @endif

                            @if ($companyLogoPath)
                                <small class="form-help">{{ $companyLogoPath }}</small>
                            @endif
                        </div>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Defaults</p>
                            <h3>Valores por defecto</h3>
                        </div>
                    </div>

                    <div class="form-grid form-grid--quote-head">
                        <label class="form-field">
                            <span>Prefijo de numeracion</span>
                            <input type="text" name="number_prefix" value="{{ $numberPrefixValue }}" required placeholder="COT">
                            @error('number_prefix')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Dias de vigencia</span>
                            <input type="number" name="default_validity_days" value="{{ $defaultValidityDaysValue }}" min="1" max="365" required>
                            @error('default_validity_days')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Impuesto por defecto (%)</span>
                            <input type="number" name="default_tax_rate" value="{{ $defaultTaxRateValue }}" min="0" max="100" step="0.01" placeholder="0.00">
                            @error('default_tax_rate')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Moneda por defecto</span>
                            <select name="default_currency_id">
                                <option value="">Selecciona una moneda</option>
                                @foreach ($currencyOptions as $currency)
                                    <option value="{{ $currency['id'] }}" @selected($defaultCurrencyValue === $currency['id'])>
                                        {{ $currency['label'] }} @if (! $currency['is_active']) - Inactiva @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('default_currency_id')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Firma y textos</p>
                            <h3>Contenido reutilizable</h3>
                        </div>
                    </div>

                    <div class="form-grid form-grid--two">
                        <label class="form-field">
                            <span>Nombre del firmante</span>
                            <input type="text" name="default_signer_name" value="{{ $defaultSignerNameValue }}" placeholder="Gustavo Noriega">
                            @error('default_signer_name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="form-field">
                            <span>Cargo del firmante</span>
                            <input type="text" name="default_signer_title" value="{{ $defaultSignerTitleValue }}" placeholder="Gerente general">
                            @error('default_signer_title')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <label class="form-field">
                        <span>Notas por defecto</span>
                        <textarea name="default_notes" rows="6" placeholder="Aclaraciones, requisitos o entregables frecuentes">{{ $defaultNotesValue }}</textarea>
                        @error('default_notes')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="form-field">
                        <span>Terminos por defecto</span>
                        <textarea name="default_terms" rows="8" placeholder="Moneda, impuestos, forma de pago, vigencia y condiciones">{{ $defaultTermsValue }}</textarea>
                        @error('default_terms')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                </article>

                <div class="hero-actions">
                    <a class="button-link button-link--ghost" href="{{ route('admin.quotations.index') }}">
                        Cancelar
                    </a>
                    <button class="button-link button-link--primary" type="submit">
                        Guardar configuracion
                    </button>
                </div>
            </div>

            <aside class="quote-editor-side">
                <article class="panel-card">
                    <div class="panel-heading">
                        <div>
                            <p class="section-kicker">Uso esperado</p>
                            <h3>Que heredan las nuevas cotizaciones</h3>
                        </div>
                    </div>

                    <ul class="bullet-list bullet-list--muted">
                        <li>Prefijo y vigencia para el numero y fecha de vencimiento inicial.</li>
                        <li>Logo y datos corporativos del emisor para cabecera y pie del PDF.</li>
                        <li>Moneda e impuesto base para el calculo comercial.</li>
                        <li>Notas y terminos que luego puedes ajustar en cada cotizacion individual.</li>
                    </ul>
                </article>
            </aside>
        </div>
    </form>
@endsection
