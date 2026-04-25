<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_name',
    'company_logo_path',
    'company_document_label',
    'company_document_number',
    'company_email',
    'company_phone',
    'company_website',
    'company_address',
    'number_prefix',
    'default_validity_days',
    'default_tax_rate',
    'default_currency_id',
    'default_notes',
    'default_terms',
    'default_signer_name',
    'default_signer_title',
])]
class QuotationSetting extends Model
{
    use HasFactory;

    public static function current(): self
    {
        return static::query()->firstOrCreate([], self::defaults());
    }

    public static function defaults(): array
    {
        return [
            'company_document_label' => 'RUC',
            'number_prefix' => 'COT',
            'default_validity_days' => 15,
            'default_tax_rate' => 0,
        ];
    }

    protected function casts(): array
    {
        return [
            'default_validity_days' => 'integer',
            'default_tax_rate' => 'decimal:2',
        ];
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function issuerSnapshot(): array
    {
        return [
            'company_name' => $this->company_name,
            'company_logo_path' => $this->company_logo_path,
            'company_document_label' => $this->company_document_label,
            'company_document_number' => $this->company_document_number,
            'company_email' => $this->company_email,
            'company_phone' => $this->company_phone,
            'company_website' => $this->company_website,
            'company_address' => $this->company_address,
            'default_signer_name' => $this->default_signer_name,
            'default_signer_title' => $this->default_signer_title,
        ];
    }
}
