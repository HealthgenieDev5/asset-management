<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'alt_phone',
        'email',
        'alt_email',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function typeLabel(): string
    {
        return $this->type === 'individual' ? 'Individual' : 'Company';
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(AssetWarranty::class);
    }

    public function amcContracts(): HasMany
    {
        return $this->hasMany(AssetAmcContract::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(AssetService::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
