<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetComplaintDetail extends Model
{
    protected $fillable = [
        'asset_complaint_id',
        'label',
        'value',
        'sort_order',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(AssetComplaint::class, 'asset_complaint_id');
    }
}
