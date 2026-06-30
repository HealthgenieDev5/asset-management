<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'code',
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

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            if (empty($vendor->code)) {
                $vendor->code = static::generateCode($vendor->name);
            }
        });
    }

    public static function generateCode(string $name): string
    {
        $base = strtoupper(Str::slug($name, '-'));
        $base = substr($base, 0, 40);
        $code = $base;
        $i    = 1;
        while (static::withTrashed()->where('code', $code)->exists()) {
            $code = $base . '-' . $i++;
        }
        return $code;
    }

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
