<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Escalation</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background: #18181b; padding: 28px 32px; }
        .header-title { color: #a3e635; font-size: 20px; font-weight: 700; margin: 0; }
        .header-sub { color: #a1a1aa; font-size: 13px; margin: 4px 0 0; }
        .body { padding: 28px 32px; }
        .alert { border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; font-size: 15px; font-weight: 600; }
        .alert-critical { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-high     { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
        .alert-medium   { background: #fefce8; border: 1px solid #fef08a; color: #92400e; }
        .alert-low      { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .section-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #71717a; margin-bottom: 12px; }
        .detail-grid { border: 1px solid #e4e4e7; border-radius: 6px; overflow: hidden; margin-bottom: 24px; }
        .detail-row { display: flex; border-bottom: 1px solid #e4e4e7; }
        .detail-row:last-child { border-bottom: none; }
        .detail-key { width: 160px; flex-shrink: 0; padding: 10px 14px; font-size: 13px; color: #71717a; background: #fafafa; font-weight: 500; }
        .detail-val { padding: 10px 14px; font-size: 13px; color: #18181b; font-weight: 500; word-break: break-word; }
        .badge { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 600; }
        .badge-critical { background: #fee2e2; color: #b91c1c; }
        .badge-high     { background: #ffedd5; color: #9a3412; }
        .badge-medium   { background: #fef9c3; color: #92400e; }
        .badge-low      { background: #dbeafe; color: #1e40af; }
        .cta { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: #a3e635; color: #18181b; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 6px; text-decoration: none; }
        .footer { padding: 20px 32px; background: #fafafa; border-top: 1px solid #e4e4e7; font-size: 12px; color: #a1a1aa; text-align: center; }
    </style>
</head>
<body>
@php
    $priority    = $complaint->priority;
    $alertClass  = "alert-{$priority}";
    $badgeClass  = "badge-{$priority}";
    $alertText   = match ($priority) {
        'critical' => 'CRITICAL complaint raised — immediate attention required.',
        'high'     => 'High-priority complaint raised — please respond promptly.',
        'medium'   => 'A new complaint has been raised and requires your attention.',
        default    => 'A new complaint has been logged in the system.',
    };
    $assetUrl = route('assets.show', [$asset, 'tab' => 'complaints']);
@endphp
<div class="wrapper">
    <div class="header">
        <p class="header-title">Complaint Escalation</p>
        <p class="header-sub">{{ config('app.name') }} — Automated notification</p>
    </div>

    <div class="body">
        <div class="alert {{ $alertClass }}">
            {{ $alertText }}
        </div>

        <p class="section-label">Complaint Details</p>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-key">Complaint #</div>
                <div class="detail-val">{{ $complaint->id }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Title</div>
                <div class="detail-val">{{ $complaint->title }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Priority</div>
                <div class="detail-val">
                    <span class="badge {{ $badgeClass }}">{{ $complaint->priority_label }}</span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Status</div>
                <div class="detail-val">{{ $complaint->status_label }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Reported By</div>
                <div class="detail-val">{{ $complaint->reported_by_name }}{{ $complaint->reported_by_email ? ' &lt;' . $complaint->reported_by_email . '&gt;' : '' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Description</div>
                <div class="detail-val">{{ Str::limit($complaint->description, 200) }}</div>
            </div>
        </div>

        <p class="section-label">Asset Details</p>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-key">Asset Code</div>
                <div class="detail-val">{{ $asset->asset_code }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Asset Name</div>
                <div class="detail-val">{{ $asset->asset_name }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Location</div>
                <div class="detail-val">{{ $complaint->location ?: '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Department</div>
                <div class="detail-val">{{ $complaint->department ?: '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Category</div>
                <div class="detail-val">{{ $asset->category?->name ?: '—' }}</div>
            </div>
        </div>

        <div class="cta">
            <a href="{{ $assetUrl }}" class="btn">View Complaint →</a>
        </div>

        <p style="font-size:13px;color:#71717a;text-align:center;margin:0;">
            This is an automated escalation from {{ config('app.name') }}.<br>
            You are receiving this because an escalation rule matches this asset's location and category.
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; Do not reply to this email.
    </div>
</div>
</body>
</html>
