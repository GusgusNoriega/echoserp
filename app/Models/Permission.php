<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'module_id', 'permission_action_id', 'description'])]
class Permission extends Model
{
    use HasFactory;

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(PermissionAction::class, 'permission_action_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
}
