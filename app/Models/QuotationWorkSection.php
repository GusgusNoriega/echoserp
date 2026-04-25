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
    'title',
])]
class QuotationWorkSection extends Model
{
    use HasFactory;

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(QuotationWorkTask::class)->orderBy('sort_order');
    }
}
