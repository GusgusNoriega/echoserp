<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_system'])]
class Module extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionAction::class, 'module_permission_action')->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
