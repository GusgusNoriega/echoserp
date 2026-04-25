<?php

namespace App\Support\Quotations;

use App\Models\Quotation;
use App\Models\QuotationLineItem;
use App\Models\QuotationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class QuotationPdfGenerator
{
    public function generate(Quotation $quotation): string
    {
        $quotation->loadMissing([
            'currency',
            'lineItems.catalogItem',
            'workSections.tasks',
        ]);

        $issuer = $this->issuer($quotation);

        $html = view('admin.quotations.pdf', [
            'formatDate' => fn ($date): string => $date ? $date->format('d/m/Y') : '-',
            'formatDecimal' => fn (mixed $value): string => filled($value) ? number_format((float) $value, 2, ',', '.') : '-',
            'formatMoney' => fn (mixed $value): string => $this->formatMoney((float) $value, $quotation),
            'issuer' => $issuer,
            'lineItems' => $this->lineItems($quotation),
            'logoUri' => $this->logoUri($issuer),
            'quotation' => $quotation,
            'statusLabel' => $this->statusLabel($quotation->status),
        ])->render();

        $mpdf = new Mpdf([
            'autoLangToFont' => false,
            'autoScriptToLang' => false,
            'default_font' => 'dejavusans',
            'default_font_size' => 9,
            'format' => 'A4',
            'margin_bottom' => 16,
            'margin_left' => 11,
            'margin_right' => 11,
            'margin_top' => 10,
            'mode' => 'utf-8',
            'packTableData' => true,
            'simpleTables' => true,
            'tempDir' => $this->tempDir(),
        ]);

        $mpdf->SetAuthor($issuer['company_name']);
        $mpdf->SetCreator(config('app.name', 'EchoERP'));
        $mpdf->SetDisplayMode('fullwidth');
        $mpdf->SetTitle('Cotizacion '.$quotation->number);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function filename(Quotation $quotation): string
    {
        $slug = Str::slug($quotation->number ?: 'cotizacion-'.$quotation->id);

        return 'cotizacion-'.($slug ?: $quotation->id).'.pdf';
    }

    private function issuer(Quotation $quotation): array
    {
        $settings = QuotationSetting::current();
        $snapshot = $quotation->issuer_snapshot ?: $settings->issuerSnapshot();

        return [
            'company_address' => $snapshot['company_address'] ?? null,
            'company_document_label' => $snapshot['company_document_label'] ?? 'RUC',
            'company_document_number' => $snapshot['company_document_number'] ?? null,
            'company_email' => $snapshot['company_email'] ?? null,
            'company_logo_path' => $snapshot['company_logo_path'] ?? $settings->company_logo_path,
            'company_name' => $snapshot['company_name'] ?? config('app.name', 'Empresa'),
            'company_phone' => $snapshot['company_phone'] ?? null,
            'company_website' => $snapshot['company_website'] ?? null,
            'default_signer_name' => $snapshot['default_signer_name'] ?? null,
            'default_signer_title' => $snapshot['default_signer_title'] ?? null,
        ];
    }

    private function lineItems(Quotation $quotation): Collection
    {
        return $quotation->lineItems->map(fn (QuotationLineItem $lineItem): array => [
            'description' => $lineItem->description,
            'discount_amount' => (float) $lineItem->discount_amount,
            'discount_label' => $this->formatMoney((float) $lineItem->discount_amount, $quotation),
            'image_uri' => $this->lineItemImageUri($lineItem->image_path),
            'line_total' => (float) $lineItem->line_total,
            'line_total_label' => $this->formatMoney((float) $lineItem->line_total, $quotation),
            'name' => $lineItem->name,
            'quantity' => (float) $lineItem->quantity,
            'quantity_label' => number_format((float) $lineItem->quantity, 2, ',', '.'),
            'specifications' => $this->specifications($lineItem),
            'type_label' => $this->typeLabel($lineItem),
            'unit_label' => $lineItem->unit_label ?: 'Unidad',
            'unit_price' => (float) $lineItem->unit_price,
            'unit_price_label' => $this->formatMoney((float) $lineItem->unit_price, $quotation),
        ]);
    }

    private function specifications(QuotationLineItem $lineItem): Collection
    {
        $specifications = $lineItem->specifications ?: $lineItem->catalogItem?->specifications ?: [];

        return collect($specifications)
            ->map(static fn (mixed $specification): string => trim((string) $specification))
            ->filter()
            ->values();
    }

    private function typeLabel(QuotationLineItem $lineItem): string
    {
        return match ($lineItem->catalogItem?->type) {
            'product' => 'Producto',
            'service' => 'Servicio',
            default => $lineItem->source_type === 'catalog' ? 'Catalogo' : 'Manual',
        };
    }

    private function formatMoney(float $amount, Quotation $quotation): string
    {
        return collect([
            $quotation->currency?->symbol,
            number_format($amount, 2, ',', '.'),
            $quotation->currency?->code,
        ])->filter()->implode(' ');
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'approved' => 'Aprobada',
            'cancelled' => 'Cancelada',
            'sent' => 'Enviada',
            default => 'Borrador',
        };
    }

    private function logoUri(array $issuer): ?string
    {
        $quotationLogoPath = $issuer['company_logo_path'] ?? null;

        if (filled($quotationLogoPath)) {
            $absolutePath = Storage::disk('quote_media')->path($quotationLogoPath);

            if (is_file($absolutePath)) {
                return $this->pathToFileUri($absolutePath);
            }
        }

        $logoPath = config('admin.brand.logo');

        if (! filled($logoPath)) {
            return null;
        }

        $absolutePath = public_path($logoPath);

        return is_file($absolutePath) ? $this->pathToFileUri($absolutePath) : null;
    }

    private function lineItemImageUri(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $absolutePath = Storage::disk('quote_media')->path($path);

        return is_file($absolutePath) ? $this->pathToFileUri($absolutePath) : null;
    }

    private function pathToFileUri(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);

        return str_starts_with($normalizedPath, '/')
            ? 'file://'.$normalizedPath
            : 'file:///'.$normalizedPath;
    }

    private function tempDir(): string
    {
        $path = storage_path('app/mpdf');

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }

        return $path;
    }
}
