<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotation_line_item_id',
    'sort_order',
    'name',
    'description',
    'unit_label',
    'price',
])]
class QuotationLineItemSubItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function quotationLineItem(): BelongsTo
    {
        return $this->belongsTo(QuotationLineItem::class);
    }
}
