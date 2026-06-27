<?php

namespace App\Http\Controllers;

use App\Concerns\FilePondDocumentHandler;
use App\Concerns\HandlesComplaintCreation;
use App\Models\Asset;
use App\Models\AssetComplaint;
use App\Models\AssetDocument;
use App\Models\AssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetComplaintController extends Controller
{
    use FilePondDocumentHandler;
    use HandlesComplaintCreation;

    public function store(Request $request, Asset $asset)
    {
        $this->stripNonFileVideoFields($request);

        $validated = $request->validate($this->complaintRules());

        $validated['asset_id'] = $asset->id;
        $validated['location'] = $asset->location;
        $validated['department'] = $asset->department;
        $validated['asset_category_id'] = $asset->asset_category_id;
        $validated['asset_subcategory_id'] = $asset->asset_subcategory_id;
        $validated['created_by'] = auth()->id();

        $complaint = AssetComplaint::create($validated);

        $this->storeComplaintVideo($request, $asset, $complaint, 'video_before', 'complaint_video_before');
        $this->storeComplaintVideo($request, $asset, $complaint, 'video_after', 'complaint_video_after');
        $this->storeComplaintDetails($request, $complaint);

        $this->triggerComplaintEscalation($asset, $complaint);

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint logged successfully.');
    }

    public function update(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $this->stripNonFileVideoFields($request);

        $validated = $request->validate([
            'status' => ['nullable', 'in:open,acknowledged,in_progress,resolved,closed,rejected'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'resolution_summary' => ['nullable', 'string'],
            'resolved_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'video_before' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
            'video_after' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:102400'],
        ]);

        $validated['updated_by'] = auth()->id();

        if (empty($validated['status'])) {
            unset($validated['status']);
        }

        $complaint->update($validated);

        $this->storeComplaintVideo($request, $asset, $complaint, 'video_before', 'complaint_video_before');
        $this->storeComplaintVideo($request, $asset, $complaint, 'video_after', 'complaint_video_after');

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint updated successfully.');
    }

    public function patchField(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $allowed = [
            'title', 'description', 'priority', 'status',
            'location', 'department',
            'reported_by_name', 'reported_by_email', 'reported_by_phone',
            'resolution_summary', 'resolved_at', 'remarks',
        ];
        $field = $request->input('field');

        abort_if(! in_array($field, $allowed, true), 422);

        $rules = [
            'title'              => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'priority'           => ['required', 'in:low,medium,high,critical'],
            'status'             => ['required', 'in:open,acknowledged,in_progress,resolved,closed,rejected'],
            'location'           => ['nullable', 'string', 'max:255'],
            'department'         => ['nullable', 'string', 'max:255'],
            'reported_by_name'   => ['nullable', 'string', 'max:255'],
            'reported_by_email'  => ['nullable', 'email', 'max:255'],
            'reported_by_phone'  => ['nullable', 'string', 'max:30'],
            'resolution_summary' => ['nullable', 'string'],
            'resolved_at'        => ['nullable', 'date'],
            'remarks'            => ['nullable', 'string'],
        ];

        $validated = $request->validate(['value' => $rules[$field]]);
        $value = $validated['value'] ?: null;

        $complaint->update([$field => $value, 'updated_by' => auth()->id()]);

        return response()->json(['ok' => true]);
    }

    public function storeDocument(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        return $this->performStoreDocument($request, $asset, $complaint, 'complaints', 'complaint_document', 'Complaint Document');
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
            'updated_by' => auth()->id(),
        ]);

        // Copy the before-repair video to the linked service entry
        $videoBefore = $complaint->videosBefore()->first();
        if ($videoBefore) {
            $service = $complaint->fresh()->service;
            $alreadyCopied = $service->documents()
                ->where('document_type', 'complaint_video_before')
                ->exists();

            if (! $alreadyCopied) {
                $originalPath = $videoBefore->file_path;
                $contents = Storage::disk('public')->get($originalPath);
                $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
                $newPath = "assets/{$asset->id}/services/{$service->id}/video_before.{$ext}";
                Storage::disk('public')->put($newPath, $contents);

                AssetDocument::create([
                    'asset_id' => $asset->id,
                    'documentable_type' => AssetService::class,
                    'documentable_id' => $service->id,
                    'document_type' => 'complaint_video_before',
                    'document_title' => 'Before-Repair Video (from Complaint #' . $complaint->id . ')',
                    'file_path' => $newPath,
                    'file_original_name' => $videoBefore->file_original_name,
                    'file_mime_type' => $videoBefore->file_mime_type,
                    'file_size' => $videoBefore->file_size,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Complaint linked to service record.');
    }
}