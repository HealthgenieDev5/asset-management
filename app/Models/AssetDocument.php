<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssetDocument extends Model
{
    protected $fillable = [
        'asset_id',
        'documentable_type',
        'documentable_id',
        'document_type',
        'document_title',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size',
        'remarks',
        'uploaded_by',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->file_mime_type ?? '', 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->file_mime_type ?? '', 'video/');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'purchase_bill'              => 'Purchase Bill',
            'bill_image'                 => 'Bill Image',
            'invoice'                    => 'Invoice',
            'warranty_card'              => 'Warranty Card',
            'warranty_activation_image'  => 'Warranty Activation Image',
            'extended_warranty_bill'     => 'Extended Warranty Bill',
            'extended_warranty_image'    => 'Extended Warranty Image',
            'insurance_copy'             => 'Insurance Copy',
            'insurance_policy'           => 'Insurance Policy',
            'puc_copy'                   => 'PUC Copy',
            'rc_copy'                    => 'RC Copy',
            'service_bill'               => 'Service Bill',
            'service_part_bill'          => 'Service Part Bill',
            'amc_bill'                   => 'AMC Bill',
            'amc_image'                  => 'AMC Image',
            'inspection_certificate'     => 'Inspection Certificate',
            'compliance_certificate'     => 'Compliance Certificate',
            'vehicle_document'           => 'Vehicle Document',
            'asset_photo'                => 'Asset Photo',
            'complaint_video_before'     => 'Before-Repair Video',
            'complaint_video_after'      => 'After-Repair Video',
            'other'                      => 'Other',
            default                      => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }
}
