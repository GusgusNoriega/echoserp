<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\QuotationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class QuotationSettingsController extends Controller
{
    public function index(): View
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

        return view('admin.quotations.settings', [
            'eyebrow' => 'Cotizaciones',
            'pageTitle' => 'Configuracion de cotizacion',
            'pageDescription' => 'Define los datos corporativos y valores por defecto que heredaran las nuevas cotizaciones.',
            'settings' => [
                'company_name' => $settings->company_name,
                'company_logo_path' => $settings->company_logo_path,
                'company_logo_url' => filled($settings->company_logo_path)
                    ? Storage::disk('quote_media')->url($settings->company_logo_path)
                    : null,
                'company_document_label' => $settings->company_document_label,
                'company_document_number' => $settings->company_document_number,
                'company_email' => $settings->company_email,
                'company_phone' => $settings->company_phone,
                'company_website' => $settings->company_website,
                'company_address' => $settings->company_address,
                'number_prefix' => $settings->number_prefix,
                'default_validity_days' => $settings->default_validity_days,
                'default_tax_rate' => $settings->default_tax_rate,
                'default_currency_id' => $settings->default_currency_id,
                'default_notes' => $settings->default_notes,
                'default_terms' => $settings->default_terms,
                'default_signer_name' => $settings->default_signer_name,
                'default_signer_title' => $settings->default_signer_title,
            ],
            'currencyOptions' => $currencies,
            'metrics' => [
                [
                    'value' => $settings->number_prefix ?: 'COT',
                    'label' => 'Prefijo',
                    'detail' => 'Base de numeracion automatica para nuevas cotizaciones.',
                ],
                [
                    'value' => str_pad((string) $settings->default_validity_days, 2, '0', STR_PAD_LEFT),
                    'label' => 'Dias de vigencia',
                    'detail' => 'Se aplican por defecto al crear una nueva cotizacion.',
                ],
                [
                    'value' => $settings->defaultCurrency?->code ?? '---',
                    'label' => 'Moneda por defecto',
                    'detail' => 'Se usa como base para importes y resumenes.',
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = QuotationSetting::current();

        $validator = Validator::make($request->all(), [
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_logo' => ['nullable', 'image', 'max:4096'],
            'remove_company_logo' => ['nullable', 'boolean'],
            'company_document_label' => ['required', 'string', 'max:50'],
            'company_document_number' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'number_prefix' => ['required', 'string', 'max:20'],
            'default_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'default_notes' => ['nullable', 'string', 'max:20000'],
            'default_terms' => ['nullable', 'string', 'max:20000'],
            'default_signer_name' => ['nullable', 'string', 'max:255'],
            'default_signer_title' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput($request->except('company_logo'));
        }

        $validated = $validator->validated();

        $settings->update([
            'company_name' => $validated['company_name'] ?: null,
            'company_logo_path' => $this->syncCompanyLogo(
                $request->file('company_logo'),
                $settings->company_logo_path,
                $request->boolean('remove_company_logo')
            ),
            'company_document_label' => trim($validated['company_document_label']),
            'company_document_number' => $validated['company_document_number'] ?: null,
            'company_email' => $validated['company_email'] ?: null,
            'company_phone' => $validated['company_phone'] ?: null,
            'company_website' => $validated['company_website'] ?: null,
            'company_address' => $validated['company_address'] ?: null,
            'number_prefix' => strtoupper(trim($validated['number_prefix'])),
            'default_validity_days' => (int) $validated['default_validity_days'],
            'default_tax_rate' => $validated['default_tax_rate'] ?? 0,
            'default_currency_id' => $validated['default_currency_id'] ?? null,
            'default_notes' => $validated['default_notes'] ?: null,
            'default_terms' => $validated['default_terms'] ?: null,
            'default_signer_name' => $validated['default_signer_name'] ?: null,
            'default_signer_title' => $validated['default_signer_title'] ?: null,
        ]);

        return redirect()
            ->route('admin.quotations.settings.index')
            ->with('status', 'Configuracion de cotizacion actualizada correctamente.');
    }

    private function syncCompanyLogo(?UploadedFile $file, ?string $currentPath, bool $removeLogo): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->store('cotizaciones/logos', 'quote_media');
        }

        if ($removeLogo) {
            return null;
        }

        return $currentPath;
    }
}
