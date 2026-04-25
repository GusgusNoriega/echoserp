<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotizacion {{ $quotation->number }}</title>
    <style>
        body {
            margin: 0;
            color: #111111;
            font-family: dejavusans, sans-serif;
            font-size: 9pt;
            line-height: 1.42;
        }

        .document {
            width: 100%;
        }

        .topbar {
            border-bottom: 3px solid #e86f12;
            padding-bottom: 12px;
        }

        .top-table,
        .meta-table,
        .summary-table,
        .totals-table,
        .footer-table {
            border-collapse: collapse;
            width: 100%;
        }

        .brand-cell {
            width: 62%;
            vertical-align: top;
        }

        .logo {
            max-height: 54px;
            max-width: 180px;
        }

        .brand-name {
            color: #111111;
            font-size: 17pt;
            font-weight: bold;
            letter-spacing: 0.2px;
        }

        .brand-subtitle,
        .muted,
        .footer-table {
            color: #4b4b4b;
        }

        .quote-box {
            background: #ffffff;
            border: 1px solid #2b2b2b;
            padding: 10px 12px;
            text-align: right;
            vertical-align: top;
        }

        .quote-title {
            color: #e86f12;
            font-size: 19pt;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-decoration: underline;
        }

        .quote-number {
            color: #182033;
            font-size: 10pt;
            font-weight: bold;
            margin-top: 3px;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            background: #e86f12;
            border: 1px solid #2b2b2b;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0 0 8px;
            padding: 5px 7px;
            text-transform: uppercase;
        }

        .meta-table td {
            border: 1px solid #2b2b2b;
            padding: 7px 8px;
            vertical-align: top;
        }

        .meta-label {
            color: #5b5b5b;
            display: block;
            font-size: 7.8pt;
            text-transform: uppercase;
        }

        .meta-value {
            color: #111111;
            font-weight: bold;
        }

        .copy-block {
            border: 1px solid #2b2b2b;
            padding: 9px 10px;
        }

        .copy-block p {
            margin: 0 0 6px;
        }

        .copy-block p:last-child {
            margin-bottom: 0;
        }

        .item-card {
            border: 1px solid #2b2b2b;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .item-head {
            background: #e86f12;
            border-bottom: 1px solid #2b2b2b;
            color: #ffffff;
            padding: 7px 8px;
        }

        .item-title {
            font-size: 10pt;
            font-weight: bold;
        }

        .badge {
            background: #ffffff;
            border: 1px solid #ffffff;
            color: #e86f12;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 2px 5px;
            text-transform: uppercase;
        }

        .item-body {
            border-collapse: collapse;
            width: 100%;
        }

        .item-body td {
            padding: 8px;
            vertical-align: top;
        }

        .item-image-cell {
            width: 30%;
        }

        .item-image {
            border: 1px solid #2b2b2b;
            max-height: 92px;
            max-width: 150px;
        }

        .image-placeholder {
            border: 1px solid #2b2b2b;
            color: #777777;
            padding: 30px 8px;
            text-align: center;
        }

        .spec-list {
            margin: 6px 0 0 0;
            padding-left: 13px;
        }

        .spec-list li {
            margin-bottom: 2px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #2b2b2b;
            padding: 6px 7px;
            text-align: right;
        }

        .summary-table th {
            background: #e86f12;
            color: #ffffff;
            font-size: 7.8pt;
            text-transform: uppercase;
        }

        .summary-table th:first-child,
        .summary-table td:first-child {
            text-align: left;
        }

        .work-section {
            border: 1px solid #2b2b2b;
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .work-section-title {
            background: #f5bf98;
            border-bottom: 1px solid #2b2b2b;
            color: #111111;
            font-weight: bold;
            padding: 7px 8px;
        }

        .work-table {
            border-collapse: collapse;
            width: 100%;
        }

        .work-table td,
        .work-table th {
            border-bottom: 1px solid #2b2b2b;
            padding: 6px 8px;
            vertical-align: top;
        }

        .work-table th {
            color: #5b5b5b;
            font-size: 7.8pt;
            text-align: left;
            text-transform: uppercase;
        }

        .totals-wrap {
            margin-left: auto;
            width: 45%;
        }

        .totals-table td {
            border-bottom: 1px solid #2b2b2b;
            padding: 7px 8px;
        }

        .totals-table td:last-child {
            font-weight: bold;
            text-align: right;
        }

        .subtotal-row td {
            background: #ffff00;
        }

        .total-row td {
            background: #00ee00;
            color: #111111;
            font-size: 11pt;
            font-weight: bold;
        }

        .signature {
            margin-top: 22px;
            page-break-inside: avoid;
            width: 42%;
        }

        .signature-line {
            border-top: 1px solid #2b2b2b;
            margin-bottom: 5px;
            padding-top: 7px;
        }

        .footer-table {
            border-top: 2px solid #e86f12;
            font-size: 7.4pt;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    @php
        $paragraphs = static fn (mixed $value) => collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->values();
        $issuerDocument = trim(($issuer['company_document_label'] ?? 'RUC').' '.($issuer['company_document_number'] ?? ''));
        $clientDocument = trim($quotation->client_document_label.' '.($quotation->client_document_number ?? ''));
    @endphp

    <htmlpagefooter name="quotation-footer">
        <table class="footer-table">
            <tr>
                <td>{{ $issuer['company_name'] }}</td>
                <td style="text-align: center;">{{ $quotation->number }}</td>
                <td style="text-align: right;">Pagina {PAGENO} de {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="quotation-footer" value="on" />

    <div class="document">
        <div class="topbar">
            <table class="top-table">
                <tr>
                    <td class="brand-cell">
                        @if ($logoUri)
                            <img class="logo" src="{{ $logoUri }}" alt="Logo">
                        @else
                            <div class="brand-name">{{ $issuer['company_name'] }}</div>
                        @endif

                        <div class="brand-subtitle">
                            @if ($issuerDocument !== '')
                                {{ $issuerDocument }}<br>
                            @endif
                            @if ($issuer['company_address'])
                                {{ $issuer['company_address'] }}<br>
                            @endif
                            @if ($issuer['company_email'])
                                {{ $issuer['company_email'] }}
                            @endif
                            @if ($issuer['company_phone'])
                                | {{ $issuer['company_phone'] }}
                            @endif
                            @if ($issuer['company_website'])
                                | {{ $issuer['company_website'] }}
                            @endif
                        </div>
                    </td>
                    <td class="quote-box">
                        <div class="quote-title">COTIZACION</div>
                        <div class="quote-number">{{ $quotation->number }}</div>
                        <div class="muted">{{ $statusLabel }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="meta-table">
                <tr>
                    <td style="width: 25%;">
                        <span class="meta-label">Emision</span>
                        <span class="meta-value">{{ $formatDate($quotation->issue_date) }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="meta-label">Valida hasta</span>
                        <span class="meta-value">{{ $formatDate($quotation->valid_until) }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="meta-label">Moneda</span>
                        <span class="meta-value">{{ $quotation->currency?->code ?? '-' }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="meta-label">Total</span>
                        <span class="meta-value">{{ $formatMoney($quotation->total) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Cliente</h2>
            <table class="meta-table">
                <tr>
                    <td style="width: 50%;">
                        <span class="meta-label">Razon social</span>
                        <span class="meta-value">{{ $quotation->client_company_name }}</span>
                    </td>
                    <td style="width: 50%;">
                        <span class="meta-label">Documento</span>
                        <span class="meta-value">{{ $clientDocument !== '' ? $clientDocument : '-' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="meta-label">Correo y telefono</span>
                        <span class="meta-value">
                            {{ collect([$quotation->client_email, $quotation->client_phone])->filter()->implode(' | ') ?: '-' }}
                        </span>
                    </td>
                    <td>
                        <span class="meta-label">Direccion</span>
                        <span class="meta-value">{{ $quotation->client_address ?: '-' }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">{{ $quotation->title }}</h2>
            @if ($paragraphs($quotation->summary)->isNotEmpty())
                <div class="copy-block">
                    @foreach ($paragraphs($quotation->summary) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="section">
            <h2 class="section-title">Productos y servicios cotizados</h2>

            @foreach ($lineItems as $item)
                <div class="item-card">
                    <div class="item-head">
                        <span class="badge">{{ $item['type_label'] }}</span>
                        <span class="item-title">{{ $item['name'] }}</span>
                    </div>

                    <table class="item-body">
                        <tr>
                            <td class="item-image-cell">
                                @if ($item['image_uri'])
                                    <img class="item-image" src="{{ $item['image_uri'] }}" alt="Imagen de {{ $item['name'] }}">
                                @else
                                    <div class="image-placeholder">Sin imagen</div>
                                @endif
                            </td>
                            <td>
                                @if ($item['description'])
                                    <div>{{ $item['description'] }}</div>
                                @endif

                                @if ($item['specifications']->isNotEmpty())
                                    <ul class="spec-list">
                                        @foreach ($item['specifications'] as $specification)
                                            <li>{{ $specification }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <table class="summary-table">
                        <tr>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                            <th>Precio unitario</th>
                            <th>Descuento</th>
                            <th>Total linea</th>
                        </tr>
                        <tr>
                            <td>{{ $item['quantity_label'] }}</td>
                            <td>{{ $item['unit_label'] }}</td>
                            <td>{{ $item['unit_price_label'] }}</td>
                            <td>{{ $item['discount_label'] }}</td>
                            <td>{{ $item['line_total_label'] }}</td>
                        </tr>
                    </table>
                </div>
            @endforeach
        </div>

        <div class="section">
            <h2 class="section-title">Plan de trabajo</h2>
            <table class="meta-table">
                <tr>
                    <td>
                        <span class="meta-label">Inicio estimado</span>
                        <span class="meta-value">{{ $formatDate($quotation->work_start_date) }}</span>
                    </td>
                    <td>
                        <span class="meta-label">Entrega estimada</span>
                        <span class="meta-value">{{ $formatDate($quotation->work_end_date) }}</span>
                    </td>
                    <td>
                        <span class="meta-label">Horas estimadas</span>
                        <span class="meta-value">{{ $formatDecimal($quotation->estimated_hours) }}</span>
                    </td>
                    <td>
                        <span class="meta-label">Dias estimados</span>
                        <span class="meta-value">{{ $formatDecimal($quotation->estimated_days) }}</span>
                    </td>
                </tr>
            </table>

            @foreach ($quotation->workSections as $section)
                <div class="work-section">
                    <div class="work-section-title">{{ $section->title }}</div>

                    @if ($section->tasks->isNotEmpty())
                        <table class="work-table">
                            <tr>
                                <th style="width: 32%;">Tarea</th>
                                <th>Detalle</th>
                                <th style="width: 18%;">Duracion</th>
                            </tr>
                            @foreach ($section->tasks as $task)
                                <tr>
                                    <td><strong>{{ $task->name }}</strong></td>
                                    <td>{{ $task->description ?: '-' }}</td>
                                    <td>{{ filled($task->duration_days) ? $formatDecimal($task->duration_days).' dias' : '-' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="section">
            <div class="totals-wrap">
                <table class="totals-table">
                    <tr class="subtotal-row">
                        <td>Subtotal</td>
                        <td>{{ $formatMoney($quotation->subtotal) }}</td>
                    </tr>
                    <tr>
                        <td>Descuento</td>
                        <td>{{ $formatMoney($quotation->discount_total) }}</td>
                    </tr>
                    <tr>
                        <td>Impuesto ({{ $formatDecimal($quotation->tax_rate) }}%)</td>
                        <td>{{ $formatMoney($quotation->tax_total) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total</td>
                        <td>{{ $formatMoney($quotation->total) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if ($paragraphs($quotation->notes)->isNotEmpty())
            <div class="section">
                <h2 class="section-title">Notas</h2>
                <div class="copy-block">
                    @foreach ($paragraphs($quotation->notes) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($paragraphs($quotation->terms_and_conditions)->isNotEmpty())
            <div class="section">
                <h2 class="section-title">Terminos y condiciones</h2>
                <div class="copy-block">
                    @foreach ($paragraphs($quotation->terms_and_conditions) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($issuer['default_signer_name'] || $issuer['default_signer_title'])
            <div class="signature">
                <div class="signature-line">
                    <strong>{{ $issuer['default_signer_name'] ?: $issuer['company_name'] }}</strong><br>
                    <span class="muted">{{ $issuer['default_signer_title'] ?: 'Representante autorizado' }}</span>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
