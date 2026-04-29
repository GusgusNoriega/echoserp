<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'type',
    'item_structure',
    'name',
    'description',
    'unit_label',
    'specifications',
    'price',
    'currency_id',
    'image_path',
    'is_active',
])]
class QuotationItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function quotationLineItems(): HasMany
    {
        return $this->hasMany(QuotationLineItem::class);
    }

    public function subItems(): HasMany
    {
        return $this->hasMany(QuotationItemSubItem::class)->orderBy('sort_order');
    }
}
