<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSetting;
use App\Models\User;
use App\Support\Quotations\QuotationPdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(): View
    {
        $settings = QuotationSetting::current()->loadMissing('defaultCurrency');

        $quotations = Quotation::query()
            ->with(['currency', 'customer', 'lineItems'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Quotation $quotation): array {
                return [
                    'id' => $quotation->id,
                    'number' => $quotation->number,
                    'status' => $quotation->status,
                    'status_label' => $this->statusOptions()->get($quotation->status, 'Sin estado'),
                    'issue_date' => $quotation->issue_date?->format('d/m/Y') ?? 'Sin fecha',
                    'valid_until' => $quotation->valid_until?->format('d/m/Y') ?? 'Sin fecha',
                    'title' => $quotation->title,
                    'client_company_name' => $quotation->client_company_name,
                    'customer_id' => $quotation->customer_id,
                    'customer_label' => $quotation->customer ? 'Registrado' : 'Manual',
                    'currency_label' => $quotation->currency?->code ?? 'Sin moneda',
                    'total' => $quotation->total,
                    'total_label' => $this->formatMoney(
                        (float) $quotation->total,
                        $quotation->currency?->symbol,
                        $quotation->currency?->code
                    ),
                    'line_items_count' => $quotation->lineItems->count(),
                ];
            });

        return view('admin.quotations.index', [
            'eyebrow' => 'Cotizaciones',
            'pageTitle' => 'Cotizaciones comerciales',
            'pageDescription' => 'Crea y administra cotizaciones completas con cliente, items, fechas del proyecto, notas y terminos listos para PDF.',
            'metrics' => [
                [
                    'value' => str_pad((string) $quotations->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Cotizaciones',
                    'detail' => 'Documentos comerciales registrados en el modulo.',
                ],
                [
                    'value' => str_pad((string) $quotations->where('status', 'draft')->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Borradores',
                    'detail' => 'Pendientes de revision o envio al cliente.',
                ],
                [
                    'value' => str_pad((string) $quotations->where('status', 'approved')->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Aprobadas',
                    'detail' => 'Listas para pasar a proyecto o ejecucion.',
                ],
                [
                    'value' => $settings->defaultCurrency?->code ?? '---',
                    'label' => 'Moneda base',
                    'detail' => 'Configurada por defecto en el modulo.',
                ],
            ],
            'quotations' => $quotations,
            'settingsPreview' => [
                'company_name' => $settings->company_name,
                'company_document_label' => $settings->company_document_label,
                'company_document_number' => $settings->company_document_number,
                'company_email' => $settings->company_email,
                'company_phone' => $settings->company_phone,
                'company_address' => $settings->company_address,
                'default_validity_days' => $settings->default_validity_days,
                'default_currency' => $settings->defaultCurrency?->code,
            ],
            'nextSteps' => [
                'El catalogo comercial sirve como base para autocompletar items, descripcion, unidad, precio e imagen.',
                'La configuracion de cotizacion define numeracion, datos de la empresa y textos por defecto.',
                'Cada cotizacion guarda sus propios importes y snapshot del emisor para no romper historicos futuros.',
            ],
        ]);
    }

    public function create(): View
    {
        return $this->formView();
    }

    public function edit(Quotation $quotation): View
    {
        $quotation->load([
            'currency',
            'customer',
            'lineItems.subItems',
            'lineItems.catalogItem.currency',
            'lineItems.catalogItem.subItems',
            'salesAdvisor',
        ]);

        return $this->formView($quotation);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        return $this->persist($request, $quotation);
    }

    public function downloadPdf(Request $request, Quotation $quotation, QuotationPdfGenerator $pdfGenerator): Response
    {
        $pdf = $pdfGenerator->generate($quotation);
        $disposition = $request->boolean('preview') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => $disposition.'; filename="'.$pdfGenerator->filename($quotation).'"',
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $imagePaths = $quotation->lineItems()->pluck('image_path')->filter()->values()->all();

        $quotation->delete();

        if ($imagePaths !== []) {
            Storage::disk('quote_media')->delete($imagePaths);
        }

        return redirect()
            ->route('admin.quotations.index')
            ->with('status', 'Cotizacion eliminada correctamente.');
    }

    private function formView(?Quotation $quotation = null): View
    {
        $settings = QuotationSetting::current()->loadMissing('defaultCurrency');

        $currencies = Currency::query()
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get()
            ->map(static fn (Currency $currency): array => [
                'id' => $currency->id,
                'name' => $currency->name,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'is_active' => $currency->is_active,
                'label' => trim($currency->name.' - '.$currency->code.' '.($currency->symbol ?? '')),
            ]);

        $catalogItems = QuotationItem::query()
            ->with(['currency', 'subItems'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function (QuotationItem $item): array {
                $specificationLines = $this->normalizeSpecificationLines($item->specifications ?? []);

                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'type_label' => $item->type === 'service' ? 'Servicio' : 'Producto',
                    'item_structure' => $item->item_structure,
                    'is_multiple' => $item->item_structure === 'multiple',
                    'name' => $item->name,
                    'lookup_label' => $this->catalogLookupLabel($item),
                    'description' => $item->description,
                    'specifications' => $specificationLines->all(),
                    'specifications_text' => $specificationLines->implode(PHP_EOL),
                    'unit_label' => $item->unit_label,
                    'price' => $item->price,
                    'currency_code' => $item->currency?->code,
                    'currency_symbol' => $item->currency?->symbol,
                    'image_path' => $item->image_path,
                    'image_url' => filled($item->image_path)
                        ? Storage::disk('quote_media')->url($item->image_path)
                        : null,
                    'sub_items' => $item->subItems
                        ->map(fn ($subItem): array => [
                            'name' => $subItem->name,
                            'description' => $subItem->description,
                            'unit_label' => $subItem->unit_label,
                            'price' => $subItem->price,
                            'price_label' => filled($subItem->price)
                                ? $this->formatMoney((float) $subItem->price, $item->currency?->symbol, $item->currency?->code)
                                : null,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        $customerOptions = Customer::query()
            ->orderByDesc('is_active')
            ->orderBy('company_name')
            ->get()
            ->map(static fn (Customer $customer): array => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'document_label' => $customer->document_label,
                'document_number' => $customer->document_number,
                'contact_name' => $customer->contact_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'is_active' => $customer->is_active,
                'label' => collect([
                    $customer->company_name,
                    trim($customer->document_label.' '.($customer->document_number ?? '')),
                ])->filter()->implode(' - '),
            ])
            ->values();

        $userOptions = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'label' => trim($user->name.' - '.$user->email),
            ])
            ->values();

        $quotationData = $quotation
            ? $this->serializeQuotation($quotation)
            : $this->defaultQuotationData($settings);

        return view('admin.quotations.form', [
            'eyebrow' => 'Cotizaciones',
            'pageTitle' => $quotation ? 'Editar cotizacion' : 'Nueva cotizacion',
            'pageDescription' => $quotation
                ? 'Actualiza el documento comercial, sus items, fechas y terminos.'
                : 'Arma una cotizacion completa con datos del cliente, items, fechas del proyecto y condiciones comerciales.',
            'quotation' => $quotationData,
            'settingsPreview' => [
                'company_name' => $settings->company_name,
                'company_document_label' => $settings->company_document_label,
                'company_document_number' => $settings->company_document_number,
                'company_email' => $settings->company_email,
                'company_phone' => $settings->company_phone,
                'company_address' => $settings->company_address,
                'default_signer_name' => $settings->default_signer_name,
                'default_signer_title' => $settings->default_signer_title,
            ],
            'catalogItems' => $catalogItems,
            'customerOptions' => $customerOptions,
            'currencyOptions' => $currencies,
            'userOptions' => $userOptions,
            'statusOptions' => $this->statusOptions(),
            'formAction' => $quotation
                ? route('admin.quotations.update', $quotation)
                : route('admin.quotations.store'),
            'formMethod' => $quotation ? 'PUT' : 'POST',
            'submitLabel' => $quotation ? 'Guardar cambios' : 'Crear cotizacion',
        ]);
    }

    private function persist(Request $request, ?Quotation $quotation = null): RedirectResponse
    {
        $validator = $this->makeValidator($request, $quotation);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $settings = QuotationSetting::current();
        $existingImagePaths = $quotation
            ? $quotation->lineItems()->pluck('image_path')->filter()->values()->all()
            : [];
        $lineItems = $this->prepareLineItems($request);
        $isEvent = $this->toBoolean($validated['is_event'] ?? false);
        $taxRate = $this->normalizeDecimal($validated['tax_rate'] ?? $settings->default_tax_rate ?? 0);
        $issueDate = $validated['issue_date'];
        $validUntil = $validated['valid_until']
            ?? Carbon::parse($issueDate)->addDays((int) ($settings->default_validity_days ?? 15))->toDateString();
        $totals = $this->calculateTotals($lineItems, $taxRate);

        DB::transaction(function () use (
            $quotation,
            $request,
            $validated,
            $settings,
            $lineItems,
            $isEvent,
            $taxRate,
            $issueDate,
            $validUntil,
            $totals
        ): void {
            $model = $quotation ?? new Quotation;

            $model->fill([
                'number' => $this->resolveQuotationNumber($validated, $model),
                'status' => $validated['status'],
                'issue_date' => $issueDate,
                'valid_until' => $validUntil,
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'client_company_name' => $validated['client_company_name'],
                'client_document_label' => $validated['client_document_label'] ?: 'RUC',
                'client_document_number' => $validated['client_document_number'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'client_phone' => $validated['client_phone'] ?? null,
                'client_address' => $validated['client_address'] ?? null,
                'currency_id' => $validated['currency_id'] ?? $settings->default_currency_id,
                'sales_advisor_id' => $validated['sales_advisor_id'] ?? null,
                'work_start_date' => $validated['work_start_date'] ?? null,
                'hide_work_plan' => true,
                'work_end_date' => $validated['work_end_date'] ?? null,
                'is_event' => $isEvent,
                'event_dates' => $isEvent ? $this->normalizeEventDates($validated['event_dates'] ?? []) : null,
                'event_setup' => $isEvent ? ($validated['event_setup'] ?? null) : null,
                'event_teardown' => $isEvent ? ($validated['event_teardown'] ?? null) : null,
                'event_location' => $isEvent ? ($validated['event_location'] ?? null) : null,
                'estimated_hours' => null,
                'estimated_days' => null,
                'hours_per_day' => null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_rate' => $taxRate,
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $validated['notes'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'issuer_snapshot' => $model->issuer_snapshot ?: $settings->issuerSnapshot(),
                'created_by' => $model->created_by ?: $request->user()?->id,
            ]);

            $model->save();

            $model->lineItems()->delete();
            $model->workSections()->delete();

            foreach ($lineItems as $lineItem) {
                $subItems = $lineItem['sub_items'] ?? [];
                unset($lineItem['sub_items']);

                $lineItemModel = $model->lineItems()->create($lineItem);

                if ($subItems !== []) {
                    $lineItemModel->subItems()->createMany($subItems);
                }
            }
        });

        $activeImagePaths = collect($lineItems)
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();
        $staleImagePaths = array_values(array_diff($existingImagePaths, $activeImagePaths));

        if ($staleImagePaths !== []) {
            Storage::disk('quote_media')->delete($staleImagePaths);
        }

        return redirect()
            ->route('admin.quotations.index')
            ->with('status', $quotation ? 'Cotizacion actualizada correctamente.' : 'Cotizacion creada correctamente.');
    }

    private function makeValidator(Request $request, ?Quotation $quotation = null)
    {
        $rules = [
            'number' => ['nullable', 'string', 'max:255', Rule::unique('quotations', 'number')->ignore($quotation?->id)],
            'status' => ['required', Rule::in($this->statusOptions()->keys()->all())],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'client_company_name' => ['required', 'string', 'max:255'],
            'client_document_label' => ['required', 'string', 'max:50'],
            'client_document_number' => ['nullable', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_address' => ['nullable', 'string', 'max:500'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'sales_advisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'work_start_date' => ['nullable', 'date'],
            'work_end_date' => ['nullable', 'date'],
            'is_event' => ['nullable', 'boolean'],
            'event_dates' => ['nullable', 'array'],
            'event_dates.*' => ['nullable', 'date', 'distinct'],
            'event_setup' => ['nullable', 'date'],
            'event_teardown' => ['nullable', 'date'],
            'event_location' => ['nullable', 'string', 'max:500'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:20000'],
            'terms_and_conditions' => ['nullable', 'string', 'max:20000'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.quotation_item_id' => ['nullable', 'integer', 'exists:quotation_items,id'],
            'line_items.*.catalog_lookup' => ['nullable', 'string', 'max:255'],
            'line_items.*.is_multiple' => ['nullable', 'boolean'],
            'line_items.*.name' => ['nullable', 'string', 'max:255'],
            'line_items.*.description' => ['nullable', 'string', 'max:5000'],
            'line_items.*.specifications_text' => ['nullable', 'string', 'max:10000'],
            'line_items.*.image' => ['nullable', 'image', 'max:4096'],
            'line_items.*.quantity' => ['nullable', 'integer', 'gt:0'],
            'line_items.*.unit_label' => ['nullable', 'string', 'max:50'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.sub_items' => ['nullable', 'array'],
            'line_items.*.sub_items.*.name' => ['nullable', 'string', 'max:255'],
            'line_items.*.sub_items.*.description' => ['nullable', 'string', 'max:5000'],
            'line_items.*.sub_items.*.unit_label' => ['nullable', 'string', 'max:50'],
            'line_items.*.sub_items.*.price' => ['nullable', 'numeric', 'min:0'],
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request): void {
            $rawLineItems = collect($request->input('line_items', []));
            $hasPopulatedLine = false;

            foreach ($rawLineItems as $index => $lineItem) {
                $quantity = filled($lineItem['quantity'] ?? null) ? (float) $lineItem['quantity'] : null;
                $unitPrice = filled($lineItem['unit_price'] ?? null) ? (float) $lineItem['unit_price'] : null;
                $discountAmount = filled($lineItem['discount_amount'] ?? null) ? (float) $lineItem['discount_amount'] : null;
                $isMultiple = $this->toBoolean($lineItem['is_multiple'] ?? false);
                $subItems = collect($lineItem['sub_items'] ?? []);
                $hasPopulatedSubItem = false;
                $subItemsTotal = 0.0;
                $hasContent = filled($lineItem['quotation_item_id'] ?? null)
                    || filled($lineItem['catalog_lookup'] ?? null)
                    || $isMultiple
                    || filled($lineItem['name'] ?? null)
                    || filled($lineItem['description'] ?? null)
                    || filled($lineItem['image_path'] ?? null)
                    || filled($lineItem['unit_label'] ?? null)
                    || $subItems->contains(function (mixed $subItem): bool {
                        if (! is_array($subItem)) {
                            return false;
                        }

                        return filled($subItem['name'] ?? null)
                            || filled($subItem['description'] ?? null)
                            || filled($subItem['unit_label'] ?? null)
                            || filled($subItem['price'] ?? null);
                    })
                    || $request->hasFile("line_items.$index.image")
                    || ($quantity !== null && round($quantity, 2) !== 1.0)
                    || ($unitPrice !== null && round($unitPrice, 2) !== 0.0)
                    || ($discountAmount !== null && round($discountAmount, 2) !== 0.0);

                if (! $hasContent) {
                    continue;
                }

                $hasPopulatedLine = true;

                if (! filled($lineItem['name'] ?? null)) {
                    $validator->errors()->add("line_items.$index.name", 'Cada item debe tener un nombre comercial.');
                }

                if (! filled($lineItem['quantity'] ?? null)) {
                    $validator->errors()->add("line_items.$index.quantity", 'Define la cantidad del item.');
                }

                if ($isMultiple) {
                    foreach ($subItems as $subItemIndex => $subItem) {
                        if (! is_array($subItem)) {
                            continue;
                        }

                        $hasSubItemContent = filled($subItem['name'] ?? null)
                            || filled($subItem['description'] ?? null)
                            || filled($subItem['unit_label'] ?? null)
                            || filled($subItem['price'] ?? null);

                        if (! $hasSubItemContent) {
                            continue;
                        }

                        $hasPopulatedSubItem = true;

                        if (filled($subItem['price'] ?? null)) {
                            $subItemsTotal += (float) $subItem['price'];
                        }

                        if (! filled($subItem['name'] ?? null)) {
                            $validator->errors()->add(
                                "line_items.$index.sub_items.$subItemIndex.name",
                                'Cada subitem debe tener un nombre.'
                            );
                        }
                    }

                    if (! $hasPopulatedSubItem) {
                        $validator->errors()->add(
                            "line_items.$index.sub_items",
                            'Agrega al menos un subitem para este producto o servicio multiple.'
                        );
                    }
                }

                $quantity = (float) ($lineItem['quantity'] ?? 0);
                $unitPrice = $isMultiple && $subItemsTotal > 0
                    ? $subItemsTotal
                    : (float) ($lineItem['unit_price'] ?? 0);
                $discountAmount = (float) ($lineItem['discount_amount'] ?? 0);

                if ($discountAmount > max($quantity * $unitPrice, 0)) {
                    $validator->errors()->add(
                        "line_items.$index.discount_amount",
                        'El descuento no puede ser mayor al subtotal del item.'
                    );
                }
            }

            if (! $hasPopulatedLine) {
                $validator->errors()->add('line_items', 'Agrega al menos un item a la cotizacion.');
            }

            if ($request->filled('work_start_date') && $request->filled('work_end_date')) {
                try {
                    if (Carbon::parse($request->input('work_end_date'))->lt(Carbon::parse($request->input('work_start_date')))) {
                        $validator->errors()->add('work_end_date', 'La fecha de finalizacion no puede ser anterior a la fecha de inicio.');
                    }
                } catch (\Throwable) {
                    // Las reglas de fecha base reportan los formatos invalidos.
                }
            }
        });

        return $validator;
    }

    private function defaultQuotationData(QuotationSetting $settings): array
    {
        $issueDate = now()->toDateString();

        return [
            'number' => '',
            'status' => 'draft',
            'issue_date' => $issueDate,
            'valid_until' => now()->addDays((int) ($settings->default_validity_days ?? 15))->toDateString(),
            'title' => '',
            'summary' => '',
            'customer_id' => '',
            'client_company_name' => '',
            'client_document_label' => 'RUC',
            'client_document_number' => '',
            'client_email' => '',
            'client_phone' => '',
            'client_address' => '',
            'currency_id' => $settings->default_currency_id,
            'sales_advisor_id' => auth()->id() ?? '',
            'work_start_date' => '',
            'work_end_date' => '',
            'is_event' => false,
            'event_dates' => [''],
            'event_setup' => '',
            'event_teardown' => '',
            'event_location' => '',
            'tax_rate' => $settings->default_tax_rate,
            'notes' => $settings->default_notes,
            'terms_and_conditions' => $settings->default_terms,
            'line_items' => [$this->emptyLineItem()],
        ];
    }

    private function serializeQuotation(Quotation $quotation): array
    {
        return [
            'number' => $quotation->number,
            'status' => $quotation->status,
            'issue_date' => $quotation->issue_date?->toDateString(),
            'valid_until' => $quotation->valid_until?->toDateString(),
            'title' => $quotation->title,
            'summary' => $quotation->summary,
            'customer_id' => $quotation->customer_id,
            'client_company_name' => $quotation->client_company_name,
            'client_document_label' => $quotation->client_document_label,
            'client_document_number' => $quotation->client_document_number,
            'client_email' => $quotation->client_email,
            'client_phone' => $quotation->client_phone,
            'client_address' => $quotation->client_address,
            'currency_id' => $quotation->currency_id,
            'sales_advisor_id' => $quotation->sales_advisor_id,
            'work_start_date' => $quotation->work_start_date?->toDateString(),
            'work_end_date' => $quotation->work_end_date?->toDateString(),
            'is_event' => $quotation->is_event,
            'event_dates' => $this->normalizeEventDates($quotation->event_dates ?? []),
            'event_setup' => $quotation->event_setup?->toDateString(),
            'event_teardown' => $quotation->event_teardown?->toDateString(),
            'event_location' => $quotation->event_location,
            'tax_rate' => $quotation->tax_rate,
            'notes' => $quotation->notes,
            'terms_and_conditions' => $quotation->terms_and_conditions,
            'line_items' => $quotation->lineItems
                ->map(function ($lineItem): array {
                    $specifications = $lineItem->specifications
                        ? $this->normalizeSpecificationLines($lineItem->specifications)
                        : ($lineItem->catalogItem
                            ? $this->catalogSpecificationLines($lineItem->catalogItem)
                            : collect());

                    return [
                        'quotation_item_id' => $lineItem->quotation_item_id,
                        'catalog_lookup' => $lineItem->catalogItem
                            ? $this->catalogLookupLabel($lineItem->catalogItem)
                            : '',
                        'is_multiple' => $lineItem->item_structure === 'multiple',
                        'name' => $lineItem->name,
                        'description' => $lineItem->description,
                        'specifications_text' => $specifications->implode(PHP_EOL),
                        'image_path' => $lineItem->image_path,
                        'image_source' => $lineItem->image_source,
                        'image_url' => filled($lineItem->image_path)
                            ? Storage::disk('quote_media')->url($lineItem->image_path)
                            : null,
                        'remove_image' => '0',
                        'quantity' => $lineItem->quantity,
                        'unit_label' => $lineItem->unit_label,
                        'unit_price' => $lineItem->unit_price,
                        'discount_amount' => $lineItem->discount_amount,
                        'sub_items' => $lineItem->subItems
                            ->map(static fn ($subItem): array => [
                                'name' => $subItem->name,
                                'description' => $subItem->description,
                                'unit_label' => $subItem->unit_label,
                                'price' => $subItem->price,
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function prepareLineItems(Request $request): array
    {
        $catalogItems = QuotationItem::query()
            ->with(['currency', 'subItems'])
            ->get(['id', 'item_structure', 'image_path', 'specifications', 'currency_id'])
            ->keyBy('id');

        return collect($request->input('line_items', []))
            ->map(function (array $lineItem, int $index) use ($request, $catalogItems): ?array {
                $name = trim((string) ($lineItem['name'] ?? ''));
                $description = trim((string) ($lineItem['description'] ?? ''));
                $unitLabel = trim((string) ($lineItem['unit_label'] ?? ''));
                $catalogId = filled($lineItem['quotation_item_id'] ?? null) ? (int) $lineItem['quotation_item_id'] : null;
                $catalogItem = $catalogId ? $catalogItems->get($catalogId) : null;
                $catalogSpecifications = $catalogItem
                    ? $this->normalizeSpecificationLines($catalogItem->specifications ?? [])
                    : collect();
                $submittedSpecifications = $this->normalizeSpecificationLines($lineItem['specifications_text'] ?? null);
                $specifications = $catalogSpecifications->isNotEmpty()
                    ? $catalogSpecifications
                    : $submittedSpecifications;
                $isMultiple = $this->toBoolean($lineItem['is_multiple'] ?? false)
                    || $catalogItem?->item_structure === 'multiple';
                $subItems = $this->normalizeLineItemSubItems($lineItem['sub_items'] ?? []);

                if ($isMultiple && $subItems === [] && $catalogItem) {
                    $subItems = $this->normalizeLineItemSubItems(
                        $catalogItem->subItems
                            ->map(static fn ($subItem): array => [
                                'name' => $subItem->name,
                                'description' => $subItem->description,
                                'unit_label' => $subItem->unit_label,
                                'price' => $subItem->price,
                            ])
                            ->all()
                    );
                }

                $subItemsTotal = collect($subItems)->sum(static fn (array $subItem): float => (float) ($subItem['price'] ?? 0));
                $existingImagePath = trim((string) ($lineItem['image_path'] ?? ''));
                $existingImageSource = trim((string) ($lineItem['image_source'] ?? ''));
                $uploadedImage = $request->file("line_items.$index.image");
                $rawQuantity = filled($lineItem['quantity'] ?? null) ? (float) $lineItem['quantity'] : null;
                $rawUnitPrice = filled($lineItem['unit_price'] ?? null) ? (float) $lineItem['unit_price'] : null;
                $rawDiscount = filled($lineItem['discount_amount'] ?? null) ? (float) $lineItem['discount_amount'] : null;

                $hasContent = $catalogId !== null
                    || $isMultiple
                    || filled($name)
                    || filled($description)
                    || filled($existingImagePath)
                    || filled($unitLabel)
                    || $subItems !== []
                    || $uploadedImage instanceof UploadedFile
                    || ($rawQuantity !== null && round($rawQuantity, 2) !== 1.0)
                    || ($rawUnitPrice !== null && round($rawUnitPrice, 2) !== 0.0)
                    || ($rawDiscount !== null && round($rawDiscount, 2) !== 0.0);

                if (! $hasContent) {
                    return null;
                }

                $quantity = $this->normalizeDecimal($lineItem['quantity'] ?? 1);
                $unitPrice = $isMultiple && $subItemsTotal > 0
                    ? round($subItemsTotal, 2)
                    : $this->normalizeDecimal($lineItem['unit_price'] ?? 0);
                $discountAmount = $this->normalizeDecimal($lineItem['discount_amount'] ?? 0);
                $lineTotal = max(($quantity * $unitPrice) - $discountAmount, 0);
                $resolvedImage = $this->resolveLineItemImage(
                    uploadedImage: $uploadedImage,
                    removeImage: $this->toBoolean($lineItem['remove_image'] ?? null),
                    catalogItem: $catalogItem,
                    existingImagePath: filled($existingImagePath) ? $existingImagePath : null,
                    existingImageSource: filled($existingImageSource) ? $existingImageSource : null,
                );

                return [
                    'source_type' => $catalogId ? 'catalog' : 'manual',
                    'quotation_item_id' => $catalogId,
                    'item_structure' => $isMultiple ? 'multiple' : 'single',
                    'name' => $name,
                    'description' => filled($description) ? $description : null,
                    'specifications' => $specifications->isNotEmpty() ? $specifications->all() : null,
                    'image_path' => $resolvedImage['path'],
                    'image_source' => $resolvedImage['source'],
                    'quantity' => $quantity,
                    'unit_label' => filled($unitLabel) ? $unitLabel : null,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'line_total' => round($lineTotal, 2),
                    'sub_items' => $isMultiple ? $subItems : [],
                ];
            })
            ->filter()
            ->values()
            ->map(static fn (array $lineItem, int $index): array => $lineItem + [
                'sort_order' => $index + 1,
            ])
            ->all();
    }

    private function normalizeEventDates(mixed $value): array
    {
        $dates = is_array($value) ? $value : [$value];

        return collect($dates)
            ->map(static fn (mixed $date): string => trim((string) $date))
            ->filter(static fn (string $date): bool => $date !== '')
            ->map(static function (string $date): ?string {
                try {
                    return Carbon::parse($date)->toDateString();
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function calculateTotals(array $lineItems, float $taxRate): array
    {
        $subtotal = collect($lineItems)->sum(static fn (array $lineItem): float => (float) $lineItem['quantity'] * (float) $lineItem['unit_price']);
        $discountTotal = collect($lineItems)->sum(static fn (array $lineItem): float => (float) $lineItem['discount_amount']);
        $base = max($subtotal - $discountTotal, 0);
        $taxTotal = round($base * ($taxRate / 100), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => $taxTotal,
            'total' => round($base + $taxTotal, 2),
        ];
    }

    private function resolveQuotationNumber(array $validated, Quotation $quotation): string
    {
        $manualNumber = trim((string) ($validated['number'] ?? ''));

        if ($manualNumber !== '') {
            return strtoupper($manualNumber);
        }

        if ($quotation->exists && filled($quotation->number)) {
            return $quotation->number;
        }

        $settings = QuotationSetting::current();
        $prefix = strtoupper(trim((string) ($settings->number_prefix ?: 'COT')));
        $clientToken = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', (string) ($validated['client_document_number'] ?? '')));
        $clientToken = $clientToken !== '' ? $clientToken : 'CLIENTE';

        $topicToken = Str::of((string) ($validated['title'] ?? ''))
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9 ]+/', ' ')
            ->trim()
            ->explode(' ')
            ->filter()
            ->first() ?? 'GEN';

        $sequence = str_pad((string) ((Quotation::query()->max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        return collect([$prefix, $clientToken, Str::limit($topicToken, 8, ''), $sequence])
            ->filter()
            ->implode('-');
    }

    private function statusOptions(): Collection
    {
        return collect([
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'approved' => 'Aprobada',
            'cancelled' => 'Cancelada',
        ]);
    }

    private function formatMoney(float $amount, ?string $symbol = null, ?string $code = null): string
    {
        $formattedAmount = number_format($amount, 2, ',', '.');

        return collect([$symbol, $formattedAmount, $code])->filter()->implode(' ');
    }

    private function normalizeDecimal(mixed $value): float
    {
        return round((float) ($value ?: 0), 2);
    }

    private function emptyLineItem(): array
    {
        return [
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
        ];
    }

    private function normalizeSpecificationLines(mixed $value): Collection
    {
        $lines = is_array($value)
            ? $value
            : (preg_split('/\r\n|\r|\n/', (string) $value) ?: []);

        return collect($lines)
            ->map(static fn (mixed $line): string => trim((string) $line))
            ->filter()
            ->values();
    }

    private function normalizeLineItemSubItems(mixed $subItems): array
    {
        if (! is_array($subItems)) {
            return [];
        }

        return collect($subItems)
            ->map(function (mixed $subItem): ?array {
                if (! is_array($subItem)) {
                    return null;
                }

                $name = trim((string) ($subItem['name'] ?? ''));
                $description = trim((string) ($subItem['description'] ?? ''));
                $unitLabel = trim((string) ($subItem['unit_label'] ?? ''));
                $price = filled($subItem['price'] ?? null)
                    ? round((float) $subItem['price'], 2)
                    : null;

                if (! filled($name) && ! filled($description) && ! filled($unitLabel) && $price === null) {
                    return null;
                }

                return [
                    'name' => $name,
                    'description' => filled($description) ? $description : null,
                    'unit_label' => filled($unitLabel) ? $unitLabel : null,
                    'price' => $price,
                ];
            })
            ->filter()
            ->values()
            ->map(static fn (array $subItem, int $index): array => $subItem + ['sort_order' => $index + 1])
            ->all();
    }

    private function catalogSpecificationLines(QuotationItem $item): Collection
    {
        $specifications = $this->normalizeSpecificationLines($item->specifications ?? []);

        if ($item->item_structure !== 'multiple') {
            return $specifications;
        }

        $subItemSpecifications = $item->subItems
            ->map(function ($subItem) use ($item): string {
                $details = collect([
                    $subItem->description,
                    $subItem->unit_label ? 'Unidad: '.$subItem->unit_label : null,
                    filled($subItem->price)
                        ? 'Precio: '.$this->formatMoney((float) $subItem->price, $item->currency?->symbol, $item->currency?->code)
                        : null,
                ])->filter()->implode(' | ');

                return trim($subItem->name.($details !== '' ? ' - '.$details : ''));
            })
            ->filter()
            ->values();

        return $specifications
            ->merge($subItemSpecifications)
            ->values();
    }

    private function catalogLookupLabel(QuotationItem $item): string
    {
        return '#'.$item->id.' - '.($item->type === 'service' ? 'Servicio' : 'Producto').' - '.$item->name;
    }

    private function resolveLineItemImage(
        ?UploadedFile $uploadedImage,
        bool $removeImage,
        ?QuotationItem $catalogItem,
        ?string $existingImagePath,
        ?string $existingImageSource
    ): array {
        if ($uploadedImage instanceof UploadedFile) {
            return [
                'path' => $uploadedImage->store('cotizaciones/line-items', 'quote_media'),
                'source' => 'uploaded',
            ];
        }

        if ($removeImage) {
            return [
                'path' => null,
                'source' => null,
            ];
        }

        if ($existingImageSource === 'uploaded' && filled($existingImagePath)) {
            return [
                'path' => $existingImagePath,
                'source' => 'uploaded',
            ];
        }

        if ($catalogItem && filled($catalogItem->image_path)) {
            if ($existingImageSource === 'catalog' && filled($existingImagePath) && $existingImagePath !== $catalogItem->image_path) {
                return [
                    'path' => $existingImagePath,
                    'source' => 'catalog',
                ];
            }

            $copiedImagePath = $this->copyCatalogImageToLineItem($catalogItem->image_path);

            return [
                'path' => $copiedImagePath,
                'source' => $copiedImagePath ? 'catalog' : null,
            ];
        }

        if (filled($existingImagePath)) {
            return [
                'path' => $existingImagePath,
                'source' => $existingImageSource ?: 'uploaded',
            ];
        }

        return [
            'path' => null,
            'source' => null,
        ];
    }

    private function copyCatalogImageToLineItem(?string $catalogImagePath): ?string
    {
        if (! filled($catalogImagePath)) {
            return null;
        }

        $disk = Storage::disk('quote_media');

        if (! $disk->exists($catalogImagePath)) {
            return null;
        }

        $extension = pathinfo($catalogImagePath, PATHINFO_EXTENSION);
        $targetPath = 'cotizaciones/line-items/'.Str::ulid().($extension ? '.'.$extension : '');
        $disk->copy($catalogImagePath, $targetPath);

        return $targetPath;
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
