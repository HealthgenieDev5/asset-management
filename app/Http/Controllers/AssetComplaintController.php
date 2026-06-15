<?php

namespace App\Http\Controllers;

use App\Mail\ComplaintEscalationMail;
use App\Models\Asset;
use App\Models\AssetComplaint;
use App\Models\AssetDocument;
use App\Models\ComplaintEscalationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AssetComplaintController extends Controller
{
    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate($this->rules());

        $validated['asset_id']             = $asset->id;
        $validated['location']             = $asset->location;
        $validated['department']           = $asset->department;
        $validated['asset_category_id']    = $asset->asset_category_id;
        $validated['asset_subcategory_id'] = $asset->asset_subcategory_id;
        $validated['created_by']           = auth()->id();

        $complaint = AssetComplaint::create($validated);

        $this->storeVideo($request, $asset, $complaint, 'video_before', 'complaint_video_before');
        $this->storeVideo($request, $asset, $complaint, 'video_after', 'complaint_video_after');

        $this->triggerEscalation($asset, $complaint);

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint logged successfully.');
    }

    public function update(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $validated = $request->validate([
            'status'             => ['required', 'in:open,acknowledged,in_progress,resolved,closed,rejected'],
            'priority'           => ['required', 'in:low,medium,high,critical'],
            'resolution_summary' => ['nullable', 'string'],
            'resolved_at'        => ['nullable', 'date'],
            'remarks'            => ['nullable', 'string'],
            'video_before'       => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:51200'],
            'video_after'        => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:51200'],
        ]);

        $validated['updated_by'] = auth()->id();

        $complaint->update($validated);

        $this->storeVideo($request, $asset, $complaint, 'video_before', 'complaint_video_before');
        $this->storeVideo($request, $asset, $complaint, 'video_after', 'complaint_video_after');

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint updated successfully.');
    }

    public function patchField(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $allowed = ['location', 'department', 'reported_by_name', 'reported_by_email', 'reported_by_phone'];
        $field   = $request->input('field');

        abort_if(! in_array($field, $allowed, true), 422);

        $rules = [
            'location'          => ['nullable', 'string', 'max:255'],
            'department'        => ['nullable', 'string', 'max:255'],
            'reported_by_name'  => ['required', 'string', 'max:255'],
            'reported_by_email' => ['nullable', 'email', 'max:255'],
            'reported_by_phone' => ['nullable', 'string', 'max:30'],
        ];

        $validated = $request->validate(['value' => $rules[$field]]);

        $complaint->update([
            $field       => $validated['value'],
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', ucwords(str_replace('_', ' ', $field)) . ' updated.');
    }

    public function destroy(Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $complaint->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint deleted.');
    }

    public function linkService(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $validated = $request->validate([
            'asset_service_id' => ['required', 'exists:asset_services,id'],
        ]);

        $complaint->update([
            'asset_service_id' => $validated['asset_service_id'],
            'updated_by'       => auth()->id(),
        ]);

        // Copy the before-repair video to the linked service entry
        $videoBefore = $complaint->videosBefore()->first();
        if ($videoBefore) {
            $service        = $complaint->fresh()->service;
            $alreadyCopied  = $service->documents()
                ->where('document_type', 'complaint_video_before')
                ->exists();

            if (! $alreadyCopied) {
                $originalPath = $videoBefore->file_path;
                $contents     = Storage::disk('public')->get($originalPath);
                $ext          = pathinfo($originalPath, PATHINFO_EXTENSION);
                $newPath      = "assets/{$asset->id}/services/{$service->id}/video_before.{$ext}";
                Storage::disk('public')->put($newPath, $contents);

                AssetDocument::create([
                    'asset_id'           => $asset->id,
                    'documentable_type'  => \App\Models\AssetService::class,
                    'documentable_id'    => $service->id,
                    'document_type'      => 'complaint_video_before',
                    'document_title'     => 'Before-Repair Video (from Complaint #' . $complaint->id . ')',
                    'file_path'          => $newPath,
                    'file_original_name' => $videoBefore->file_original_name,
                    'file_mime_type'     => $videoBefore->file_mime_type,
                    'file_size'          => $videoBefore->file_size,
                    'uploaded_by'        => auth()->id(),
                ]);
            }
        }

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint linked to service record.');
    }

    private function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['required', 'string'],
            'reported_by_name' => ['required', 'string', 'max:255'],
            'reported_by_email'=> ['nullable', 'email', 'max:255'],
            'reported_by_phone'=> ['nullable', 'string', 'max:30'],
            'priority'         => ['required', 'in:low,medium,high,critical'],
            'remarks'          => ['nullable', 'string'],
            'video_before'     => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:51200'],
            'video_after'      => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:51200'],
        ];
    }

    private function storeVideo(Request $request, Asset $asset, AssetComplaint $complaint, string $field, string $docType): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);
        $path = $file->store("assets/{$asset->id}/complaints/{$complaint->id}", 'public');

        AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetComplaint::class,
            'documentable_id'    => $complaint->id,
            'document_type'      => $docType,
            'document_title'     => $docType === 'complaint_video_before' ? 'Before-Repair Video' : 'After-Repair Video',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);
    }

    private function triggerEscalation(Asset $asset, AssetComplaint $complaint): void
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
