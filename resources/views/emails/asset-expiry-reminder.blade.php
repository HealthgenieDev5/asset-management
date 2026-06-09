<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Expiry Reminder</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }
        .header { background: #18181b; padding: 28px 32px; }
        .header-title { color: #a3e635; font-size: 20px; font-weight: 700; margin: 0; }
        .header-sub { color: #a1a1aa; font-size: 13px; margin: 4px 0 0; }
        .body { padding: 28px 32px; }
        .alert { border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; font-size: 15px; font-weight: 600; }
        .alert-expired { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-soon    { background: #fefce8; border: 1px solid #fef08a; color: #92400e; }
        .alert-normal  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .section-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #71717a; margin-bottom: 12px; }
        .detail-grid { border: 1px solid #e4e4e7; border-radius: 6px; overflow: hidden; margin-bottom: 24px; }
        .detail-row { display: flex; border-bottom: 1px solid #e4e4e7; }
        .detail-row:last-child { border-bottom: none; }
        .detail-key { width: 160px; flex-shrink: 0; padding: 10px 14px; font-size: 13px; color: #71717a; background: #fafafa; font-weight: 500; }
        .detail-val { padding: 10px 14px; font-size: 13px; color: #18181b; font-weight: 500; word-break: break-all; }
        .badge { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 600; }
        .badge-expired { background: #fee2e2; color: #b91c1c; }
        .badge-soon    { background: #fef9c3; color: #92400e; }
        .badge-normal  { background: #dcfce7; color: #166534; }
        .cta { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: #a3e635; color: #18181b; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 6px; text-decoration: none; }
        .footer { padding: 20px 32px; background: #fafafa; border-top: 1px solid #e4e4e7; font-size: 12px; color: #a1a1aa; text-align: center; }
    </style>
</head>
<body>
@php
    $days    = $reminder['days_until_expiry'];
    $expired = $days < 0;
    $soon    = !$expired && $days <= 30;

    if ($expired) {
        $alertClass  = 'alert-expired';
        $badgeClass  = 'badge-expired';
        $alertText   = 'This item has EXPIRED. Immediate action required.';
        $statusLabel = 'Expired';
        $daysLabel   = abs($days) . ' day(s) ago';
    } elseif ($soon) {
        $alertClass  = 'alert-soon';
        $badgeClass  = 'badge-soon';
        $alertText   = "This item expires in {$days} day(s). Please renew soon.";
        $statusLabel = 'Expiring Soon';
        $daysLabel   = $days . ' day(s) remaining';
    } else {
        $alertClass  = 'alert-normal';
        $badgeClass  = 'badge-normal';
        $alertText   = "Upcoming expiry in {$days} day(s). Plan for renewal.";
        $statusLabel = 'Active';
        $daysLabel   = $days . ' day(s) remaining';
    }
@endphp
<div class="wrapper">
    <div class="header">
        <p class="header-title">Asset Expiry Reminder</p>
        <p class="header-sub">{{ config('app.name') }} — Automated notification</p>
    </div>

    <div class="body">
        <div class="alert {{ $alertClass }}">
            {{ $alertText }}
        </div>

        <p class="section-label">Asset Details</p>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-key">Asset Code</div>
                <div class="detail-val">{{ $reminder['asset_code'] }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Asset Name</div>
                <div class="detail-val">{{ $reminder['asset_name'] }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Expiry Type</div>
                <div class="detail-val">{{ $reminder['type'] }}</div>
            </div>
            @if ($reminder['detail'])
            <div class="detail-row">
                <div class="detail-key">Detail</div>
                <div class="detail-val">{{ $reminder['detail'] }}</div>
            </div>
            @endif
            <div class="detail-row">
                <div class="detail-key">Expiry Date</div>
                <div class="detail-val">{{ $reminder['expiry_date'] }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Days</div>
                <div class="detail-val">{{ $daysLabel }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-key">Status</div>
                <div class="detail-val">
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>

        <div class="cta">
            <a href="{{ $reminder['asset_url'] }}" class="btn">View Asset →</a>
        </div>

        <p style="font-size:13px;color:#71717a;text-align:center;margin:0;">
            This is an automated reminder from {{ config('app.name') }}.<br>
            To adjust reminder settings, edit the reminder days on the asset's detail page.
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; Do not reply to this email.
    </div>
</div>
</body>
</html>
