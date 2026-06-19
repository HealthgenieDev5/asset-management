<?php

namespace App\Console\Commands;

use App\Mail\AssetExpiryReminderMail;
use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetExtendedWarranty;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetWarranty;
use App\Models\AssetService;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetServicePart;
use App\Models\AssetSmartReminder;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendAssetReminderEmails extends Command
{
    protected $signature = 'assets:send-reminders
                            {--dry-run : List reminders that would be sent without actually sending}
                            {--days=   : Override: only send if expiry is within this many days}';

    protected $description = 'Send expiry reminder emails for warranties, AMC, insurance, vehicle compliance, and service records';

    private int $sent    = 0;
    private int $skipped = 0;
    private string $today;

    public function handle(): int
    {
        $this->today  = now()->toDateString();
        $dryRun       = $this->option('dry-run');
        $daysOverride = $this->option('days') !== null ? (int) $this->option('days') : null;

        $this->info($dryRun ? '[DRY RUN] Scanning expiry reminders…' : 'Sending expiry reminder emails…');

        $this->processWarrantyEntries($dryRun, $daysOverride);
        $this->processSmartReminders($dryRun, $daysOverride);
        $this->processMaintenanceSchedules($dryRun, $daysOverride);

        $verb = $dryRun ? 'would be sent' : 'sent';
        $this->newLine();
        $this->info("Done. {$this->sent} email(s) {$verb}, {$this->skipped} skipped.");

        return self::SUCCESS;
    }

    // ── Source processors ────────────────────────────────────────────────────

    private function processWarrantyEntries(bool $dryRun, ?int $daysOverride): void
    {
        AssetWarranty::where('status', 'active')
            ->with(['asset.createdBy', 'asset.meterLogs'])
            ->get()
            ->each(function (AssetWarranty $warranty) use ($dryRun, $daysOverride) {
                $asset = $warranty->asset;
                if (! $asset) {
                    return;
                }

                $scopeLabel = $warranty->scope === 'part'
                    ? ($warranty->part_name . ' — ')
                    : '';
                $typeLabel = $scopeLabel . $warranty->warrantyTypeLabel() . ' Warranty';

                if ($warranty->isTimeBased()) {
                    if (! $warranty->expiry_date) {
                        return;
                    }
                    $this->maybeNotify(
                        recipient:   $asset->createdBy,
                        daysWindow:  $daysOverride ?? ($warranty->reminder_before_days ?? 30),
                        expiryDate:  $warranty->expiry_date,
                        reminderKey: "warranty-entry:{$warranty->id}",
                        payload: [
                            'asset_code' => $asset->asset_code,
                            'asset_name' => $asset->asset_name,
                            'type'       => $typeLabel,
                            'detail'     => $warranty->details,
                            'tab'        => 'warranty',
                            'asset'      => $asset,
                        ],
                        dryRun: $dryRun,
                    );
                } else {
                    // Counter-based warranty
                    $current   = $warranty->latestCounter();
                    $limit     = $warranty->counter_limit;
                    $threshold = $warranty->reminder_before_units ?? 500;
                    $unit      = $warranty->unitLabel();

                    if ($current === null || $limit === null) {
                        return;
                    }

                    $remaining = $limit - $current;

                    if ($remaining > $threshold) {
                        $this->skipped++;
                        return;
                    }

                    $overrideEmail = config('mail.asset_reminder_recipient');
                    $toEmail = $overrideEmail ?: ($asset->createdBy?->email ?? null);
                    if (! $toEmail) {
                        $this->skipped++;
                        return;
                    }

                    $reminderKey = "warranty-entry:{$warranty->id}";
                    if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
                        $this->skipped++;
                        $this->line("  ⊘ Already sent [{$reminderKey}] → {$toEmail}");
                        return;
                    }

                    $detail = "Limit: {$limit} {$unit} · Current: {$current} {$unit} · Remaining: {$remaining} {$unit}";

                    if ($dryRun) {
                        $flag = $remaining <= 0 ? '🔴' : ($remaining <= ($threshold / 2) ? '🟡' : '🟢');
                        $this->line("  {$flag}  [{$typeLabel}] {$asset->asset_code} — {$toEmail} — {$remaining} {$unit} left");
                    } else {
                        Mail::to($toEmail)->send(new AssetExpiryReminderMail([
                            'asset_code'        => $asset->asset_code,
                            'asset_name'        => $asset->asset_name,
                            'type'              => $typeLabel,
                            'detail'            => $detail,
                            'expiry_date'       => "at {$limit} {$unit}",
                            'days_until_expiry' => $remaining,
                            'asset_url'         => route('assets.show', [$asset, 'tab' => 'warranty']),
                        ]));
                        $this->recordSent($toEmail, $reminderKey);
                        $this->line("  ✓ Sent [{$typeLabel}] {$asset->asset_code} → {$toEmail}");
                    }

                    $this->sent++;
                }
            });
    }

    private function processOriginalWarranties(bool $dryRun, ?int $daysOverride): void
    {
        Asset::whereNotNull('warranty_lapse_date')->with('createdBy')->get()
            ->each(function (Asset $asset) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $asset->createdBy,
                    daysWindow:  $daysOverride ?? ($asset->warranty_reminder_before_days ?? 30),
                    expiryDate:  $asset->warranty_lapse_date,
                    reminderKey: "warranty:{$asset->id}",
                    payload: [
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->asset_name,
                        'type'       => 'Original Warranty',
                        'detail'     => $asset->warranty_details,
                        'tab'        => 'warranty',
                        'asset'      => $asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processCounterWarranties(bool $dryRun, ?int $daysOverride): void
    {
        Asset::whereNotNull('warranty_counter_limit')->with(['createdBy', 'services'])->get()
            ->each(function (Asset $asset) use ($dryRun) {
                $current   = $asset->latestWarrantyCounter();
                $limit     = $asset->warranty_counter_limit;
                $threshold = $asset->warranty_reminder_before_units ?? 500;
                $unit      = $asset->warrantyUnitLabel();

                if ($current === null || $limit === null) {
                    return;
                }

                $remaining = $limit - $current;

                if ($remaining > $threshold) {
                    $this->skipped++;
                    return;
                }

                $toEmail = config('mail.asset_reminder_recipient') ?: ($asset->createdBy?->email ?? null);
                if (! $toEmail) {
                    $this->skipped++;
                    return;
                }

                $reminderKey = "warranty-counter:{$asset->id}";
                if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
                    $this->skipped++;
                    $this->line("  ⊘ Already sent [{$reminderKey}] → {$toEmail}");
                    return;
                }

                $detail = "Limit: {$limit} {$unit} · Current: {$current} {$unit} · Remaining: {$remaining} {$unit}";

                if ($dryRun) {
                    $flag = $remaining <= 0 ? '🔴' : ($remaining <= ($threshold / 2) ? '🟡' : '🟢');
                    $this->line("  {$flag}  [Original Warranty — {$unit}] {$asset->asset_code} — {$toEmail} — {$remaining} {$unit} left");
                } else {
                    Mail::to($toEmail)->send(new AssetExpiryReminderMail([
                        'asset_code'        => $asset->asset_code,
                        'asset_name'        => $asset->asset_name,
                        'type'              => 'Original Warranty',
                        'detail'            => $detail,
                        'expiry_date'       => "at {$limit} {$unit}",
                        'days_until_expiry' => $remaining,
                        'asset_url'         => route('assets.show', [$asset, 'tab' => 'warranty']),
                    ]));
                    $this->recordSent($toEmail, $reminderKey);
                    $this->line("  ✓ Sent [Original Warranty — {$unit}] {$asset->asset_code} → {$toEmail}");
                }

                $this->sent++;
            });
    }

    private function processExtendedCounterWarranties(bool $dryRun, ?int $daysOverride): void
    {
        AssetExtendedWarranty::whereNotNull('extended_warranty_counter_limit')->with(['asset.createdBy', 'asset.services'])->get()
            ->each(function (AssetExtendedWarranty $ew) use ($dryRun) {
                $asset     = $ew->asset;
                $current   = $asset?->latestWarrantyCounter();
                $limit     = $ew->extended_warranty_counter_limit;
                $threshold = $ew->extended_warranty_reminder_before_units ?? 500;
                $unit      = $asset?->warrantyUnitLabel() ?? 'units';

                if ($current === null || $limit === null || ! $asset) {
                    return;
                }

                $remaining = $limit - $current;

                if ($remaining > $threshold) {
                    $this->skipped++;
                    return;
                }

                $toEmail = config('mail.asset_reminder_recipient') ?: ($asset->createdBy?->email ?? null);
                if (! $toEmail) {
                    $this->skipped++;
                    return;
                }

                $reminderKey = "ext-warranty-counter:{$ew->id}";
                if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
                    $this->skipped++;
                    $this->line("  ⊘ Already sent [{$reminderKey}] → {$toEmail}");
                    return;
                }

                $detail = "Limit: {$limit} {$unit} · Current: {$current} {$unit} · Remaining: {$remaining} {$unit}";

                if ($dryRun) {
                    $flag = $remaining <= 0 ? '🔴' : ($remaining <= ($threshold / 2) ? '🟡' : '🟢');
                    $this->line("  {$flag}  [Extended Warranty — {$unit}] {$asset->asset_code} — {$toEmail} — {$remaining} {$unit} left");
                } else {
                    Mail::to($toEmail)->send(new AssetExpiryReminderMail([
                        'asset_code'        => $asset->asset_code,
                        'asset_name'        => $asset->asset_name,
                        'type'              => 'Extended Warranty',
                        'detail'            => $detail,
                        'expiry_date'       => "at {$limit} {$unit}",
                        'days_until_expiry' => $remaining,
                        'asset_url'         => route('assets.show', [$asset, 'tab' => 'ext-warranty']),
                    ]));
                    $this->recordSent($toEmail, $reminderKey);
                    $this->line("  ✓ Sent [Extended Warranty — {$unit}] {$asset->asset_code} → {$toEmail}");
                }

                $this->sent++;
            });
    }

    private function processExtendedWarranties(bool $dryRun, ?int $daysOverride): void
    {
        AssetExtendedWarranty::whereNotNull('extended_warranty_date_to')->with('asset.createdBy')->get()
            ->each(function (AssetExtendedWarranty $ew) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $ew->asset?->createdBy,
                    daysWindow:  $daysOverride ?? ($ew->reminder_before_days ?? 30),
                    expiryDate:  $ew->extended_warranty_date_to,
                    reminderKey: "ext-warranty:{$ew->id}",
                    payload: [
                        'asset_code' => $ew->asset?->asset_code,
                        'asset_name' => $ew->asset?->asset_name,
                        'type'       => 'Extended Warranty',
                        'detail'     => $ew->extended_warranty_vendor,
                        'tab'        => 'ext-warranty',
                        'asset'      => $ew->asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processAmcContracts(bool $dryRun, ?int $daysOverride): void
    {
        AssetAmcContract::whereNotNull('amc_date_to')->with('asset.createdBy')->get()
            ->each(function (AssetAmcContract $amc) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $amc->asset?->createdBy,
                    daysWindow:  $daysOverride ?? ($amc->reminder_before_days ?? 30),
                    expiryDate:  $amc->amc_date_to,
                    reminderKey: "amc:{$amc->id}",
                    payload: [
                        'asset_code' => $amc->asset?->asset_code,
                        'asset_name' => $amc->asset?->asset_name,
                        'type'       => 'AMC Contract',
                        'detail'     => $amc->vendor_name ?: $amc->contract_number,
                        'tab'        => 'amc',
                        'asset'      => $amc->asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processInsurancePolicies(bool $dryRun, ?int $daysOverride): void
    {
        AssetInsurancePolicy::whereNotNull('policy_date_to')->with('asset.createdBy')->get()
            ->each(function (AssetInsurancePolicy $policy) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $policy->asset?->createdBy,
                    daysWindow:  $daysOverride ?? ($policy->reminder_before_days ?? 30),
                    expiryDate:  $policy->policy_date_to,
                    reminderKey: "insurance:{$policy->id}",
                    payload: [
                        'asset_code' => $policy->asset?->asset_code,
                        'asset_name' => $policy->asset?->asset_name,
                        'type'       => 'Insurance Policy',
                        'detail'     => $policy->insurer_name ?: $policy->policy_number,
                        'tab'        => 'insurance',
                        'asset'      => $policy->asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processPucExpiry(bool $dryRun, ?int $daysOverride): void
    {
        Asset::whereNotNull('puc_expiry_date')->with('createdBy')->get()
            ->each(function (Asset $asset) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $asset->createdBy,
                    daysWindow:  $daysOverride ?? ($asset->puc_reminder_before_days ?? 30),
                    expiryDate:  $asset->puc_expiry_date,
                    reminderKey: "puc:{$asset->id}",
                    payload: [
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->asset_name,
                        'type'       => 'PUC / Emission Certificate',
                        'detail'     => 'Pollution Under Control Certificate',
                        'tab'        => 'overview',
                        'asset'      => $asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processFitnessExpiry(bool $dryRun, ?int $daysOverride): void
    {
        Asset::whereNotNull('fitness_expiry_date')->with('createdBy')->get()
            ->each(function (Asset $asset) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $asset->createdBy,
                    daysWindow:  $daysOverride ?? ($asset->fitness_reminder_before_days ?? 30),
                    expiryDate:  $asset->fitness_expiry_date,
                    reminderKey: "fitness:{$asset->id}",
                    payload: [
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->asset_name,
                        'type'       => 'Fitness Certificate',
                        'detail'     => 'Vehicle Fitness Certificate',
                        'tab'        => 'overview',
                        'asset'      => $asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processRoadTaxExpiry(bool $dryRun, ?int $daysOverride): void
    {
        Asset::whereNotNull('road_tax_expiry_date')->with('createdBy')->get()
            ->each(function (Asset $asset) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $asset->createdBy,
                    daysWindow:  $daysOverride ?? ($asset->road_tax_reminder_before_days ?? 30),
                    expiryDate:  $asset->road_tax_expiry_date,
                    reminderKey: "road-tax:{$asset->id}",
                    payload: [
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->asset_name,
                        'type'       => 'Road Tax',
                        'detail'     => 'Road Tax Renewal',
                        'tab'        => 'overview',
                        'asset'      => $asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processNextServiceDates(bool $dryRun, ?int $daysOverride): void
    {
        AssetService::whereNotNull('next_service_date')->with('asset.createdBy')->get()
            ->each(function (AssetService $svc) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $svc->asset?->createdBy,
                    daysWindow:  $daysOverride ?? ($svc->next_service_reminder_before_days ?? 30),
                    expiryDate:  $svc->next_service_date,
                    reminderKey: "next-service:{$svc->id}",
                    payload: [
                        'asset_code' => $svc->asset?->asset_code,
                        'asset_name' => $svc->asset?->asset_name,
                        'type'       => 'Next Service Due',
                        'detail'     => $svc->service_type_label . ($svc->service_agency ? ' — ' . $svc->service_agency : ''),
                        'tab'        => 'services',
                        'asset'      => $svc->asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processCertificationExpiry(bool $dryRun, ?int $daysOverride): void
    {
        AssetService::whereNotNull('certification_expiry')->with('asset.createdBy')->get()
            ->each(function (AssetService $svc) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $svc->asset?->createdBy,
                    daysWindow:  $daysOverride ?? ($svc->certification_reminder_before_days ?? 30),
                    expiryDate:  $svc->certification_expiry,
                    reminderKey: "cert-expiry:{$svc->id}",
                    payload: [
                        'asset_code' => $svc->asset?->asset_code,
                        'asset_name' => $svc->asset?->asset_name,
                        'type'       => 'Certification Expiry',
                        'detail'     => $svc->service_type_label . ($svc->service_agency ? ' — ' . $svc->service_agency : ''),
                        'tab'        => 'services',
                        'asset'      => $svc->asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    private function processPartWarranties(bool $dryRun, ?int $daysOverride): void
    {
        AssetServicePart::whereNotNull('warranty_till')->with('asset.createdBy')->get()
            ->each(function (AssetServicePart $part) use ($dryRun, $daysOverride) {
                $this->maybeNotify(
                    recipient:   $part->asset?->createdBy,
                    daysWindow:  $daysOverride ?? 30,
                    expiryDate:  $part->warranty_till,
                    reminderKey: "part-warranty:{$part->id}",
                    payload: [
                        'asset_code' => $part->asset?->asset_code,
                        'asset_name' => $part->asset?->asset_name,
                        'type'       => 'Part Warranty',
                        'detail'     => $part->part_name . ($part->purchased_from ? ' — ' . $part->purchased_from : ''),
                        'tab'        => 'parts',
                        'asset'      => $part->asset,
                    ],
                    dryRun: $dryRun,
                );
            });
    }

    // ── Core send logic with duplicate guard ─────────────────────────────────

    private function maybeNotify(
        ?User           $recipient,
        int             $daysWindow,
        CarbonInterface $expiryDate,
        string          $reminderKey,
        array           $payload,
        bool            $dryRun,
    ): void {
        $overrideEmail = config('mail.asset_reminder_recipient');
        $toEmail = $overrideEmail ?: ($recipient?->email ?? null);

        if (! $toEmail) {
            $this->skipped++;
            return;
        }

        $daysLeft = (int) ($expiryDate->copy()->startOfDay()->diffInDays(now()->startOfDay()) * ($expiryDate->gte(now()->startOfDay()) ? 1 : -1));

        if ($daysLeft > $daysWindow) {
            $this->skipped++;
            return;
        }

        // Duplicate guard: skip if already sent today for this recipient+key
        if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
            $this->skipped++;
            $this->line("  ⊘ Already sent [{$reminderKey}] → {$toEmail}");
            return;
        }

        $mailPayload = array_merge($payload, [
            'expiry_date'       => $expiryDate->format('d M Y'),
            'days_until_expiry' => $daysLeft,
            'asset_url'         => $payload['asset']
                ? route('assets.show', [$payload['asset'], 'tab' => $payload['tab']])
                : '#',
        ]);
        unset($mailPayload['asset']);

        if ($dryRun) {
            $flag = $daysLeft < 0 ? '🔴' : ($daysLeft <= 7 ? '🟡' : '🟢');
            $this->line("  {$flag}  [{$payload['type']}] {$payload['asset_code']} — {$toEmail} — {$daysLeft}d");
        } else {
            Mail::to($toEmail)->send(new AssetExpiryReminderMail($mailPayload));
            $this->recordSent($toEmail, $reminderKey);
            $this->line("  ✓ Sent [{$payload['type']}] {$payload['asset_code']} → {$toEmail}");
        }

        $this->sent++;
    }

    private function processSmartReminders(bool $dryRun, ?int $daysOverride): void
    {
        $this->info('Processing smart reminders…');

        AssetSmartReminder::where('is_active', true)
            ->with('asset.createdBy', 'asset.meterLogs')
            ->get()
            ->each(function (AssetSmartReminder $sr) use ($dryRun, $daysOverride) {
                $asset = $sr->asset;
                if (! $asset) return;

                $overrideEmail = config('mail.asset_reminder_recipient');
                $toEmail       = $overrideEmail ?: ($asset->createdBy?->email ?? null);
                if (! $toEmail) { $this->skipped++; return; }

                if ($sr->isTimeBased()) {
                    // ── Date mode: exact-day match ──────────────────────────
                    if (! $sr->expiry_date) return;

                    $daysLeft = (int) now()->startOfDay()->diffInDays($sr->expiry_date->startOfDay(), false);

                    foreach ($sr->reminder_days as $threshold) {
                        $effective = $daysOverride ?? $threshold;
                        if ($daysLeft !== $effective) continue;

                        $key = "smart-reminder-{$sr->id}-{$threshold}";
                        if (! $dryRun && $this->alreadySentToday($toEmail, $key)) {
                            $this->skipped++;
                            $this->line("  ⊘ Already sent [{$key}] → {$toEmail}");
                            continue;
                        }

                        $payload = [
                            'asset_code'        => $asset->asset_code,
                            'asset_name'        => $asset->asset_name,
                            'type'              => $sr->reminder_name,
                            'detail'            => "Reminder: {$threshold} day(s) before expiry",
                            'expiry_date'       => $sr->expiry_date->format('d M Y'),
                            'days_until_expiry' => $daysLeft,
                            'asset_url'         => route('assets.show', [$asset, 'tab' => 'reminders']),
                            'tab'               => 'reminders',
                        ];

                        if ($dryRun) {
                            $flag = $daysLeft < 0 ? '🔴' : ($daysLeft <= 7 ? '🟡' : '🟢');
                            $this->line("  {$flag}  [{$sr->reminder_name}] {$asset->asset_code} — {$toEmail} — {$daysLeft}d (threshold: {$threshold}d)");
                        } else {
                            Mail::to($toEmail)->send(new AssetExpiryReminderMail($payload));
                            $this->recordSent($toEmail, $key);
                            $this->line("  ✓ Sent [{$sr->reminder_name}] {$asset->asset_code} → {$toEmail}");
                        }
                        $this->sent++;
                    }
                } else {
                    // ── Meter/Count mode: range check (remaining <= threshold) ──
                    $remaining = $sr->remainingUnits();
                    if ($remaining === null) {
                        $this->skipped++;
                        return;
                    }

                    $unit = $sr->threshold_unit ?? 'units';

                    foreach ($sr->reminder_days as $threshold) {
                        if ($remaining > $threshold) continue;

                        $key = "smart-reminder-{$sr->id}-units-{$threshold}";
                        if (! $dryRun && $this->alreadySentToday($toEmail, $key)) {
                            $this->skipped++;
                            $this->line("  ⊘ Already sent [{$key}] → {$toEmail}");
                            continue;
                        }

                        $payload = [
                            'asset_code'        => $asset->asset_code,
                            'asset_name'        => $asset->asset_name,
                            'type'              => $sr->reminder_name,
                            'detail'            => "Reminder: {$remaining} {$unit} remaining (threshold: {$threshold} {$unit})",
                            'expiry_date'       => number_format((int) $sr->counter_limit) . ' ' . $unit . ' limit',
                            'days_until_expiry' => $remaining,
                            'asset_url'         => route('assets.show', [$asset, 'tab' => 'reminders']),
                            'tab'               => 'reminders',
                        ];

                        if ($dryRun) {
                            $flag = $remaining <= 0 ? '🔴' : '🟡';
                            $this->line("  {$flag}  [{$sr->reminder_name}] {$asset->asset_code} — {$toEmail} — {$remaining} {$unit} left (threshold: {$threshold})");
                        } else {
                            Mail::to($toEmail)->send(new AssetExpiryReminderMail($payload));
                            $this->recordSent($toEmail, $key);
                            $this->line("  ✓ Sent [{$sr->reminder_name}] {$asset->asset_code} → {$toEmail}");
                        }
                        $this->sent++;
                    }
                }
            });
    }

    private function processMaintenanceSchedules(bool $dryRun, ?int $daysOverride): void
    {
        $this->info('Processing maintenance schedules…');

        AssetMaintenanceSchedule::where('is_active', true)
            ->with('asset.createdBy', 'asset.services')
            ->get()
            ->each(function (AssetMaintenanceSchedule $schedule) use ($dryRun, $daysOverride) {
                $asset = $schedule->asset;
                if (! $asset) {
                    return;
                }

                $overrideEmail = config('mail.asset_reminder_recipient');
                $toEmail       = $overrideEmail ?: ($asset->createdBy?->email ?? null);

                if (! $toEmail) {
                    $this->skipped++;
                    return;
                }

                $isDate = $schedule->schedule_type === 'date';

                if ($isDate) {
                    if (! $schedule->next_due_date) {
                        return;
                    }
                    $daysLeft = (int) now()->startOfDay()->diffInDays(
                        $schedule->next_due_date->startOfDay(), false
                    );
                    foreach ($schedule->reminder_thresholds as $threshold) {
                        $effective = $daysOverride ?? $threshold;
                        if ($daysLeft !== $effective) {
                            continue;
                        }
                        $reminderKey = "maintenance-schedule-{$schedule->id}-{$threshold}";
                        if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
                            $this->skipped++;
                            continue;
                        }
                        $payload = [
                            'asset_code'        => $asset->asset_code,
                            'asset_name'        => $asset->asset_name,
                            'type'              => $schedule->schedule_name,
                            'detail'            => $schedule->serviceTypeLabel() . ' schedule — due in ' . $threshold . ' day(s)',
                            'expiry_date'       => $schedule->next_due_date->format('d M Y'),
                            'days_until_expiry' => $daysLeft,
                            'asset_url'         => route('assets.show', [$asset, 'tab' => 'schedules']),
                            'tab'               => 'schedules',
                        ];
                        if ($dryRun) {
                            $this->line("  🟡  [{$schedule->schedule_name}] {$asset->asset_code} — {$toEmail} — {$daysLeft}d before due");
                        } else {
                            Mail::to($toEmail)->send(new AssetExpiryReminderMail($payload));
                            $this->recordSent($toEmail, $reminderKey);
                            $this->line("  ✓ Sent [{$schedule->schedule_name}] {$asset->asset_code} → {$toEmail}");
                        }
                        $this->sent++;
                    }
                    return;
                }

                // Mileage-based
                if ($schedule->schedule_type === 'mileage') {
                    $latestKm = $asset->services()->orderByDesc('service_date')->value('mileage_reading');
                    if ($latestKm === null || $schedule->last_done_km === null || $schedule->interval_km === null) {
                        return;
                    }
                    $remaining = (int) $schedule->interval_km - ($latestKm - (int) $schedule->last_done_km);
                    foreach ($schedule->reminder_thresholds as $threshold) {
                        if ($remaining > $threshold) {
                            continue;
                        }
                        $reminderKey = "maintenance-schedule-{$schedule->id}-km-{$threshold}";
                        if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
                            $this->skipped++;
                            continue;
                        }
                        $payload = [
                            'asset_code'        => $asset->asset_code,
                            'asset_name'        => $asset->asset_name,
                            'type'              => $schedule->schedule_name,
                            'detail'            => number_format($remaining) . ' km remaining (reminder at ' . number_format($threshold) . ' km)',
                            'expiry_date'       => 'In ' . number_format(max(0, $remaining)) . ' km',
                            'days_until_expiry' => $remaining,
                            'asset_url'         => route('assets.show', [$asset, 'tab' => 'schedules']),
                            'tab'               => 'schedules',
                        ];
                        if ($dryRun) {
                            $this->line("  🟡  [{$schedule->schedule_name}] {$asset->asset_code} — {$toEmail} — {$remaining} km remaining");
                        } else {
                            Mail::to($toEmail)->send(new AssetExpiryReminderMail($payload));
                            $this->recordSent($toEmail, $reminderKey);
                            $this->line("  ✓ Sent [{$schedule->schedule_name}] {$asset->asset_code} → {$toEmail}");
                        }
                        $this->sent++;
                    }
                    return;
                }

                // Operating hours-based
                if ($schedule->schedule_type === 'operating_hours') {
                    $latestHrs = $asset->services()->orderByDesc('service_date')->value('meter_reading');
                    if ($latestHrs === null || $schedule->last_done_hours === null || $schedule->interval_hours === null) {
                        return;
                    }
                    $remaining = (int) $schedule->interval_hours - ($latestHrs - (int) $schedule->last_done_hours);
                    foreach ($schedule->reminder_thresholds as $threshold) {
                        if ($remaining > $threshold) {
                            continue;
                        }
                        $reminderKey = "maintenance-schedule-{$schedule->id}-hr-{$threshold}";
                        if (! $dryRun && $this->alreadySentToday($toEmail, $reminderKey)) {
                            $this->skipped++;
                            continue;
                        }
                        $payload = [
                            'asset_code'        => $asset->asset_code,
                            'asset_name'        => $asset->asset_name,
                            'type'              => $schedule->schedule_name,
                            'detail'            => number_format($remaining) . ' hrs remaining (reminder at ' . number_format($threshold) . ' hrs)',
                            'expiry_date'       => 'In ' . number_format(max(0, $remaining)) . ' hours',
                            'days_until_expiry' => $remaining,
                            'asset_url'         => route('assets.show', [$asset, 'tab' => 'schedules']),
                            'tab'               => 'schedules',
                        ];
                        if ($dryRun) {
                            $this->line("  🟡  [{$schedule->schedule_name}] {$asset->asset_code} — {$toEmail} — {$remaining} hrs remaining");
                        } else {
                            Mail::to($toEmail)->send(new AssetExpiryReminderMail($payload));
                            $this->recordSent($toEmail, $reminderKey);
                            $this->line("  ✓ Sent [{$schedule->schedule_name}] {$asset->asset_code} → {$toEmail}");
                        }
                        $this->sent++;
                    }
                }
            });
    }

    private function alreadySentToday(string $email, string $key): bool
    {
        return DB::table('asset_reminder_email_logs')
            ->where('recipient_email', $email)
            ->where('reminder_key', $key)
            ->where('sent_date', $this->today)
            ->exists();
    }

    private function recordSent(string $email, string $key): void
    {
        DB::table('asset_reminder_email_logs')->insertOrIgnore([
            'recipient_email' => $email,
            'reminder_key'    => $key,
            'sent_date'       => $this->today,
        ]);
    }
}
