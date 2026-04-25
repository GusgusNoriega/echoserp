<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_name',
    'document_label',
    'document_number',
    'contact_name',
    'email',
    'phone',
    'address',
    'notes',
    'is_active',
])]
class Customer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
