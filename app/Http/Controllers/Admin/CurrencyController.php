<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(): View
    {
        $currencies = Currency::query()
            ->withCount('quotationItems')
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency): array {
                return [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'label' => trim($currency->code.' '.($currency->symbol ?? '')),
                    'is_active' => $currency->is_active,
                    'status_label' => $currency->is_active ? 'Activa' : 'Inactiva',
                    'quotation_items_count' => $currency->quotation_items_count,
                    'updated_at' => $currency->updated_at?->format('d/m/Y') ?? 'Sin fecha',
                ];
            });

        return view('admin.quotations.currencies', [
            'eyebrow' => 'Cotizaciones',
            'pageTitle' => 'Monedas',
            'pageDescription' => 'Gestiona las monedas disponibles para asignar precios a productos y servicios.',
            'metrics' => [
                [
                    'value' => str_pad((string) $currencies->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Monedas registradas',
                    'detail' => $currencies->where('is_active', true)->count().' activas para nuevos precios.',
                ],
                [
                    'value' => str_pad((string) $currencies->where('quotation_items_count', '>', 0)->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Monedas en uso',
                    'detail' => 'Asignadas a productos o servicios del catalogo.',
                ],
                [
                    'value' => str_pad((string) $currencies->where('symbol', '!=', null)->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Con simbolo',
                    'detail' => 'Listas para mostrar montos con mejor lectura.',
                ],
            ],
            'currencies' => $currencies,
            'nextSteps' => [
                'Mantener codigos cortos como COP, USD o EUR ayuda a ordenar precios y exportaciones.',
                'Si una moneda deja de usarse, puedes marcarla inactiva sin afectar items historicos.',
                'Antes de eliminar una moneda revisa si algun producto o servicio ya la esta usando.',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'currencyCreate')
                ->withInput()
                ->with('modal', 'currency-create-modal');
        }

        $validated = $validator->validated();

        Currency::query()->create([
            'name' => $validated['name'],
            'code' => $this->normalizeCurrencyCode($validated['code']),
            'symbol' => $validated['symbol'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.quotations.currencies.index')
            ->with('status', 'Moneda creada correctamente.');
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $validator = $this->makeValidator($request, $currency);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'currencyEdit')
                ->withInput()
                ->with('modal', 'currency-edit-modal-'.$currency->id);
        }

        $validated = $validator->validated();

        $currency->update([
            'name' => $validated['name'],
            'code' => $this->normalizeCurrencyCode($validated['code']),
            'symbol' => $validated['symbol'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.quotations.currencies.index')
            ->with('status', 'Moneda actualizada correctamente.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->quotationItems()->exists()) {
            return redirect()
                ->route('admin.quotations.currencies.index')
                ->with('error', 'No puedes eliminar una moneda que ya esta asignada a productos o servicios.');
        }

        $currency->delete();

        return redirect()
            ->route('admin.quotations.currencies.index')
            ->with('status', 'Moneda eliminada correctamente.');
    }

    private function makeValidator(Request $request, ?Currency $currency = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9._-]+$/'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $currency): void {
            $code = $this->normalizeCurrencyCode($request->input('code'));

            if ($code === '') {
                $validator->errors()->add('code', 'El codigo de moneda no es valido.');

                return;
            }

            $query = Currency::query()->whereRaw('UPPER(code) = ?', [$code]);

            if ($currency instanceof Currency) {
                $query->whereKeyNot($currency->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('code', 'Ya existe una moneda con ese codigo.');
            }
        });

        return $validator;
    }

    private function normalizeCurrencyCode(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }
}
