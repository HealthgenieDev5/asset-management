<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMeterLog extends Model
{
    use HasAuditLog;
    protected $fillable = [
        'asset_id',
        'unit',
        'reading_value',
        'logged_at',
        'notes',
        'evidence_path',
        'evidence_original_name',
        'created_by',
    ];

    protected $casts = [
        'logged_at'     => 'date',
        'reading_value' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function auditModelLabel(): string
    {
        return 'Meter Log';
    }

    protected static function auditFieldLabels(): array
    {
        return [
            'unit'          => 'Unit',
            'reading_value' => 'Reading',
            'logged_at'     => 'Logged At',
        ];
    }
}
