<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMeterLog extends Model
{
    protected $fillable = [
        'asset_id',
        'unit',
        'reading_value',
        'logged_at',
        'notes',
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
}
