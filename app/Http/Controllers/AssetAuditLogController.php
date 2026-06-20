<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAuditLog;

class AssetAuditLogController extends Controller
{
    public function index(Asset $asset)
    {
        $auditLogs = AssetAuditLog::where('asset_id', $asset->id)
            ->with('causer:id,name')
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('assets.tabs.history', compact('asset', 'auditLogs'));
    }
}
