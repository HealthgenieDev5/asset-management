<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetComplaint;
use App\Models\AssetComplaintComment;
use Illuminate\Http\Request;

class AssetComplaintCommentController extends Controller
{
    public function store(Request $request, Asset $asset, AssetComplaint $complaint)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);

        $validated = $request->validate([
            'comment'     => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        AssetComplaintComment::create([
            'complaint_id' => $complaint->id,
            'user_id'      => auth()->id(),
            'comment'      => $validated['comment'],
            'is_internal'  => (bool) ($validated['is_internal'] ?? false),
        ]);

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Comment added.');
    }

    public function destroy(Asset $asset, AssetComplaint $complaint, AssetComplaintComment $comment)
    {
        abort_if($complaint->asset_id !== $asset->id, 403);
        abort_if($comment->complaint_id !== $complaint->id, 403);

        $comment->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'complaints'])
            ->with('success', 'Comment deleted.');
    }
}
