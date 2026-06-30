<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'code', 'status'];

    protected function casts(): array
    {
        return ['status' => 'string'];
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(AssetSubcategory::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
