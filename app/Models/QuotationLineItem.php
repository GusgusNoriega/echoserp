<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quotation_id',
    'sort_order',
    'source_type',
    'quotation_item_id',
    'item_structure',
    'name',
    'description',
    'specifications',
    'image_path',
    'image_source',
    'quantity',
    'unit_label',
    'unit_price',
    'discount_amount',
    'line_total',
])]
class QuotationLineItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'specifications' => 'array',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    public function subItems(): HasMany
    {
        return $this->hasMany(QuotationLineItemSubItem::class)->orderBy('sort_order');
    }
}
