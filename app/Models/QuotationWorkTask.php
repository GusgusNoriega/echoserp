<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotation_work_section_id',
    'sort_order',
    'name',
    'description',
    'duration_days',
])]
class QuotationWorkTask extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'duration_days' => 'decimal:2',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuotationWorkSection::class, 'quotation_work_section_id');
    }
}
