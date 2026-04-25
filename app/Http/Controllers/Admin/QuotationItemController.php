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
            ->with('currency')
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
            'name' => $validated['name'],
            'description' => $validated['description'],
            'unit_label' => $validated['unit_label'] ?? null,
            'specifications' => $this->normalizeSpecifications($validated['specifications_text'] ?? null),
            'price' => $this->normalizePrice($validated['price'] ?? null),
            'currency_id' => $validated['currency_id'] ?? null,
            'image_path' => $this->storeImage($request->file('image')),
            'is_active' => $request->boolean('is_active'),
        ]);

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
            'name' => $validated['name'],
            'description' => $validated['description'],
            'unit_label' => $validated['unit_label'] ?? null,
            'specifications' => $this->normalizeSpecifications($validated['specifications_text'] ?? null),
            'price' => $this->normalizePrice($validated['price'] ?? null),
            'currency_id' => $validated['currency_id'] ?? null,
            'image_path' => $this->syncImage(
                $request->file('image'),
                $quotationItem->image_path,
                $request->boolean('remove_image')
            ),
            'is_active' => $request->boolean('is_active'),
        ]);

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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'unit_label' => ['nullable', 'string', 'max:50'],
            'specifications_text' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $hasPrice = filled($request->input('price'));
            $hasCurrency = filled($request->input('currency_id'));

            if ($hasPrice && ! $hasCurrency) {
                $validator->errors()->add('currency_id', 'Selecciona una moneda para el precio indicado.');
            }

            if ($hasCurrency && ! $hasPrice) {
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

    private function formatPriceLabel(QuotationItem $item): ?string
    {
        if ($item->price === null || ! $item->currency) {
            return null;
        }

        $amount = number_format((float) $item->price, 2, ',', '.');

        return collect([
            $item->currency->symbol,
            $amount,
            $item->currency->code,
        ])->filter()->implode(' ');
    }
}
