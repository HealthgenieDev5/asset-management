<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\ComplaintEscalationMail;
use App\Models\Asset;
use App\Models\AssetComplaint;
use App\Models\AssetComplaintDetail;
use App\Models\AssetDocument;
use App\Models\ComplaintEscalationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

trait HandlesComplaintCreation
{
    protected function complaintRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'reported_by_name' => ['required', 'string', 'max:255'],
            'reported_by_email' => ['nullable', 'email', 'max:255'],
            'reported_by_phone' => ['nullable', 'string', 'max:30'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'remarks' => ['nullable', 'string'],
            'video_before' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'video_after' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'detail_labels.*' => ['nullable', 'string', 'max:255'],
            'detail_values.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function storeComplaintDetails(Request $request, AssetComplaint $complaint): void
    {
        $labels = $request->input('detail_labels', []);
        $values = $request->input('detail_values', []);

        $sortOrder = 0;
        foreach ($labels as $index => $label) {
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            AssetComplaintDetail::create([
                'asset_complaint_id' => $complaint->id,
                'label' => $label,
                'value' => $values[$index] ?? null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    protected function storeComplaintVideo(Request $request, Asset $asset, AssetComplaint $complaint, string $field, string $docType): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);
        $path = $file->store("assets/{$asset->id}/complaints/{$complaint->id}", 'public');

        AssetDocument::create([
            'asset_id' => $asset->id,
            'documentable_type' => AssetComplaint::class,
            'documentable_id' => $complaint->id,
            'document_type' => $docType,
            'document_title' => $docType === 'complaint_video_before' ? 'Before-Repair Video' : 'After-Repair Video',
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    protected function triggerComplaintEscalation(Asset $asset, AssetComplaint $complaint): void
    {
        if (! $asset->location || ! $asset->asset_category_id) {
            return;
        }

        $rule = ComplaintEscalationRule::findForComplaint($asset->location, $asset->asset_category_id);

        if (! $rule || empty($rule->notify_emails)) {
            return;
        }

        foreach ($rule->notify_emails as $email) {
            Mail::to($email)->queue(new ComplaintEscalationMail($asset, $complaint));
        }
    }
}
