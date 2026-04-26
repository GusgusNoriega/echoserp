<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number',
    'status',
    'issue_date',
    'valid_until',
    'title',
    'summary',
    'customer_id',
    'client_company_name',
    'client_document_label',
    'client_document_number',
    'client_email',
    'client_phone',
    'client_address',
    'currency_id',
    'work_start_date',
    'hide_work_plan',
    'work_end_date',
    'estimated_hours',
    'estimated_days',
    'hours_per_day',
    'subtotal',
    'discount_total',
    'tax_rate',
    'tax_total',
    'total',
    'notes',
    'terms_and_conditions',
    'issuer_snapshot',
    'created_by',
])]
class Quotation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'work_start_date' => 'date',
            'hide_work_plan' => 'boolean',
            'work_end_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'estimated_days' => 'decimal:2',
            'hours_per_day' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'issuer_snapshot' => 'array',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuotationLineItem::class)->orderBy('sort_order');
    }

    public function workSections(): HasMany
    {
        return $this->hasMany(QuotationWorkSection::class)->orderBy('sort_order');
    }
}
