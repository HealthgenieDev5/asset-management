<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssetAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'auditable_type',
        'auditable_id',
        'event',
        'causer_id',
        'ip_address',
        'old_values',
        'new_values',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventBadgeColor(): string
    {
        return match ($this->event) {
            'created'  => 'bg-green-400/10 text-green-400',
            'updated'  => 'bg-blue-400/10 text-blue-400',
            'deleted'  => 'bg-red-400/10 text-red-400',
            'restored' => 'bg-amber-400/10 text-amber-400',
            default    => 'bg-zinc-400/10 text-zinc-400',
        };
    }

    public function modelLabel(): string
    {
        $map = [
            'App\\Models\\Asset'                    => 'Asset',
            'App\\Models\\AssetWarranty'            => 'Warranty',
            'App\\Models\\AssetAmcContract'         => 'AMC Contract',
            'App\\Models\\AssetInsurancePolicy'     => 'Insurance Policy',
            'App\\Models\\AssetService'             => 'Service Record',
            'App\\Models\\AssetServicePart'         => 'Service Part',
            'App\\Models\\AssetMaintenanceSchedule' => 'Maintenance Schedule',
            'App\\Models\\AssetMeterLog'            => 'Meter Log',
            'App\\Models\\AssetSmartReminder'       => 'Smart Reminder',
            'App\\Models\\AssetComplaint'           => 'Complaint',
            'App\\Models\\AssetDocument'            => 'Document',
        ];

        return $map[$this->auditable_type] ?? class_basename($this->auditable_type);
    }
}
