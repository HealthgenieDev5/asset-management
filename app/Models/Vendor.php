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
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'service_types',
        'sla_response_hours',
        'sla_resolution_days',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'service_types' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            if (empty($vendor->code)) {
                $vendor->code = static::generateCode();
            }
        });
    }

    protected static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('code', 'like', 'VEN-%')
            ->orderByDesc('id')
            ->value('code');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'VEN-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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

    public function serviceTypesLabel(): string
    {
        if (empty($this->service_types)) {
            return '—';
        }

        $labels = [
            'warranty' => 'Warranty',
            'amc'      => 'AMC',
            'service'  => 'Service',
            'all'      => 'All',
        ];

        return implode(', ', array_map(fn ($t) => $labels[$t] ?? ucfirst($t), $this->service_types));
    }

    public function slaLabel(): string
    {
        $parts = [];
        if ($this->sla_response_hours !== null) {
            $parts[] = $this->sla_response_hours . 'h';
        }
        if ($this->sla_resolution_days !== null) {
            $parts[] = $this->sla_resolution_days . 'd';
        }

        return $parts ? implode(' / ', $parts) : '—';
    }
}
