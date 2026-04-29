<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\QuotationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuotationItemController extends Controller
{
    public function index(): View
    {
        $currencies = Currency::query()
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency): array {
                return [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'is_active' => $currency->is_active,
                    'label' => $currency->name.' - '.$currency->code.($currency->symbol ? ' - '.$currency->symbol : ''),
                ];
            });

        $items = QuotationItem::query()
            ->with(['currency', 'subItems'])
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get()
            ->map(function (QuotationItem $item): array {
                $specifications = collect($item->specifications ?? [])
                    ->filter(static fn (mixed $specification): bool => filled($specification))
                    ->values();

                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'type_label' => $item->type === 'service' ? 'Servicio' : 'Producto',
                    'item_structure' => $item->item_structure,
                    'item_structure_label' => $item->item_structure === 'multiple' ? 'Multiple' : 'Normal',
                    'name' => $item->name,
                    'description' => $item->description,
                    'unit_label' => $item->unit_label,
                    'specifications' => $specifications->all(),
                    'specifications_count' => $specifications->count(),
                    'specifications_text' => $specifications->implode(PHP_EOL),
                    'price' => $item->price,
                    'price_label' => $this->formatPriceLabel($item),
                    'currency_id' => $item->currency_id,
                    'currency_label' => $item->currency
                        ? trim($item->currency->code.' '.($item->currency->symbol ?? ''))
                        : null,
                    'sub_items' => $item->subItems
                        ->map(fn ($subItem): array => [
                            'name' => $subItem->name,
                            'description' => $subItem->description,
                            'unit_label' => $subItem->unit_label,
                            'price' => $subItem->price,
                            'price_label' => filled($subItem->price)
                                ? $this->formatPriceLabel($item, (float) $subItem->price)
                                : null,
                        ])
                        ->values()
                        ->all(),
                    'sub_items_count' => $item->subItems->count(),
                    'image_path' => $item->image_path,
                    'image_url' => filled($item->image_path)
                        ? Storage::disk('quote_media')->url($item->image_path)
                        : null,
                    'is_active' => $item->is_active,
                    'status_label' => $item->is_active ? 'Activo' : 'Inactivo',
                    'updated_at' => $item->updated_at?->format('d/m/Y') ?? 'Sin fecha',
                ];
            });

        return view('admin.quotations.catalog', [
            'eyebrow' => 'Cotizaciones',
            'pageTitle' => 'Catalogo comercial',
            'pageDescription' => 'Gestiona productos y servicios reutilizables para autocompletar futuras cotizaciones.',
            'metrics' => [
                [
                    'value' => str_pad((string) $items->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Items registrados',
                    'detail' => 'Catalogo total entre productos y servicios.',
                ],
                [
                    'value' => str_pad((string) $items->where('type', 'product')->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Productos',
                    'detail' => 'Elementos fisicos con ficha comercial lista.',
                ],
                [
                    'value' => str_pad((string) $items->where('type', 'service')->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Servicios',
                    'detail' => 'Ofertas intangibles con descripcion y alcance.',
                ],
                [
                    'value' => str_pad((string) $items->filter(static fn (array $item): bool => filled($item['price_label']))->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Con precio',
                    'detail' => $currencies->where('is_active', true)->count().' monedas activas disponibles.',
                ],
            ],
            'items' => $items,
            'currencyOptions' => $currencies,
            'nextSteps' => [
                'Usa una especificacion por linea para mantener fichas claras y faciles de editar.',
                'Si un item aun no tiene precio puedes guardarlo igual y completarlo despues desde el mismo catalogo.',
                'Antes de asignar nuevos precios revisa que la moneda exista y este activa en la pantalla de monedas.',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'quotationItemCreate')
                ->withInput($request->except('image'))
                ->with('modal', 'quotation-item-create-modal');
        }

        $validated = $validator->validated();

        QuotationItem::query()->create([
            'type' => $validated['type'],
            'item_structure' => $validated['item_structure'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'unit_label' => $validated['unit_label'] ?? null,
            'specifications' => $this->normalizeSpecifications($validated['specifications_text'] ?? null),
            'price' => $this->resolveItemPrice($validated['item_structure'], $validated['price'] ?? null, $validated['sub_items'] ?? []),
            'currency_id' => $validated['currency_id'] ?? null,
            'image_path' => $this->storeImage($request->file('image')),
            'is_active' => $request->boolean('is_active'),
        ])->subItems()->createMany($this->normalizeSubItems($validated['item_structure'], $validated['sub_items'] ?? []));

        return redirect()
            ->route('admin.quotations.catalog.index')
            ->with('status', 'Elemento de cotizacion creado correctamente.');
    }

    public function update(Request $request, QuotationItem $quotationItem): RedirectResponse
    {
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'quotationItemEdit')
                ->withInput($request->except('image'))
                ->with('modal', 'quotation-item-edit-modal-'.$quotationItem->id);
        }

        $validated = $validator->validated();

        $quotationItem->update([
            'type' => $validated['type'],
            'item_structure' => $validated['item_structure'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'unit_label' => $validated['unit_label'] ?? null,
            'specifications' => $this->normalizeSpecifications($validated['specifications_text'] ?? null),
            'price' => $this->resolveItemPrice($validated['item_structure'], $validated['price'] ?? null, $validated['sub_items'] ?? []),
            'currency_id' => $validated['currency_id'] ?? null,
            'image_path' => $this->syncImage(
                $request->file('image'),
                $quotationItem->image_path,
                $request->boolean('remove_image')
            ),
            'is_active' => $request->boolean('is_active'),
        ]);

        $quotationItem->subItems()->delete();
        $quotationItem->subItems()->createMany($this->normalizeSubItems($validated['item_structure'], $validated['sub_items'] ?? []));

        return redirect()
            ->route('admin.quotations.catalog.index')
            ->with('status', 'Elemento de cotizacion actualizado correctamente.');
    }

    public function destroy(QuotationItem $quotationItem): RedirectResponse
    {
        if (filled($quotationItem->image_path)) {
            Storage::disk('quote_media')->delete($quotationItem->image_path);
        }

        $quotationItem->delete();

        return redirect()
            ->route('admin.quotations.catalog.index')
            ->with('status', 'Elemento de cotizacion eliminado correctamente.');
    }

    private function makeValidator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['product', 'service'])],
            'item_structure' => ['required', Rule::in(['single', 'multiple'])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'unit_label' => ['nullable', 'string', 'max:50'],
            'specifications_text' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'sub_items' => ['nullable', 'array'],
            'sub_items.*.name' => ['nullable', 'string', 'max:255'],
            'sub_items.*.description' => ['nullable', 'string', 'max:5000'],
            'sub_items.*.unit_label' => ['nullable', 'string', 'max:50'],
            'sub_items.*.price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $itemStructure = $request->input('item_structure', 'single');
            $hasPrice = filled($request->input('price'));
            $subItems = collect($request->input('sub_items', []));
            $hasPopulatedSubItem = false;
            $hasSubItemPrice = false;
            $hasCurrency = filled($request->input('currency_id'));

            if ($itemStructure === 'multiple') {
                foreach ($subItems as $index => $subItem) {
                    if (! is_array($subItem)) {
                        continue;
                    }

                    $hasContent = filled($subItem['name'] ?? null)
                        || filled($subItem['description'] ?? null)
                        || filled($subItem['unit_label'] ?? null)
                        || filled($subItem['price'] ?? null);

                    if (! $hasContent) {
                        continue;
                    }

                    $hasPopulatedSubItem = true;

                    if (filled($subItem['price'] ?? null)) {
                        $hasSubItemPrice = true;
                    }

                    if (! filled($subItem['name'] ?? null)) {
                        $validator->errors()->add("sub_items.$index.name", 'Cada subitem debe tener un nombre.');
                    }
                }

                if (! $hasPopulatedSubItem) {
                    $validator->errors()->add('sub_items', 'Agrega al menos un subitem para este producto o servicio multiple.');
                }
            }

            if (($hasPrice || $hasSubItemPrice) && ! $hasCurrency) {
                $validator->errors()->add('currency_id', 'Selecciona una moneda para el precio indicado.');
            }

            if ($hasCurrency && ! $hasPrice && ! $hasSubItemPrice) {
                $validator->errors()->add('price', 'Ingresa un precio si seleccionas una moneda.');
            }
        });

        return $validator;
    }

    private function normalizeSpecifications(?string $value): ?array
    {
        $specifications = collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();

        return $specifications === [] ? null : $specifications;
    }

    private function normalizePrice(mixed $value): ?float
    {
        if (! filled($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function resolveItemPrice(string $itemStructure, mixed $price, array $subItems): ?float
    {
        if ($itemStructure !== 'multiple') {
            return $this->normalizePrice($price);
        }

        $total = collect($this->normalizeSubItems($itemStructure, $subItems))
            ->sum(static fn (array $subItem): float => (float) ($subItem['price'] ?? 0));

        return $total > 0 ? round($total, 2) : null;
    }

    private function normalizeSubItems(string $itemStructure, array $subItems): array
    {
        if ($itemStructure !== 'multiple') {
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
                $price = $this->normalizePrice($subItem['price'] ?? null);

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

    private function storeImage(?UploadedFile $file): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $file->store('cotizaciones', 'quote_media');
    }

    private function syncImage(?UploadedFile $file, ?string $currentPath, bool $removeImage): ?string
    {
        if ($file instanceof UploadedFile) {
            if (filled($currentPath)) {
                Storage::disk('quote_media')->delete($currentPath);
            }

            return $this->storeImage($file);
        }

        if ($removeImage && filled($currentPath)) {
            Storage::disk('quote_media')->delete($currentPath);

            return null;
        }

        return $currentPath;
    }

    private function formatPriceLabel(QuotationItem $item, ?float $amount = null): ?string
    {
        if (($amount === null && $item->price === null) || ! $item->currency) {
            return null;
        }

        $amount = number_format($amount ?? (float) $item->price, 2, ',', '.');

        return collect([
            $item->currency->symbol,
            $amount,
            $item->currency->code,
        ])->filter()->implode(' ');
    }
}
