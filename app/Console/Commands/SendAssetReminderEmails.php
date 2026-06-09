<?php

namespace App\Console\Commands;

use App\Mail\AssetExpiryReminderMail;
use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetExtendedWarranty;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetService;
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

        $this->processOriginalWarranties($dryRun, $daysOverride);
        $this->processExtendedWarranties($dryRun, $daysOverride);
        $this->processAmcContracts($dryRun, $daysOverride);
        $this->processInsurancePolicies($dryRun, $daysOverride);
        $this->processPucExpiry($dryRun, $daysOverride);
        $this->processFitnessExpiry($dryRun, $daysOverride);
        $this->processRoadTaxExpiry($dryRun, $daysOverride);
        $this->processNextServiceDates($dryRun, $daysOverride);
        $this->processCertificationExpiry($dryRun, $daysOverride);

        $verb = $dryRun ? 'would be sent' : 'sent';
        $this->newLine();
        $this->info("Done. {$this->sent} email(s) {$verb}, {$this->skipped} skipped.");

        return self::SUCCESS;
    }

    // ── Source processors ────────────────────────────────────────────────────

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

    // ── Core send logic with duplicate guard ─────────────────────────────────

    private function maybeNotify(
        ?User           $recipient,
        int             $daysWindow,
        CarbonInterface $expiryDate,
        string          $reminderKey,
        array           $payload,
        bool            $dryRun,
    ): void {
        if (! $recipient || ! $recipient->email) {
            $this->skipped++;
            return;
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay(), false);

        if ($daysLeft > $daysWindow) {
            $this->skipped++;
            return;
        }

        // Duplicate guard: skip if already sent today for this recipient+key
        if (! $dryRun && $this->alreadySentToday($recipient->email, $reminderKey)) {
            $this->skipped++;
            $this->line("  ⊘ Already sent [{$reminderKey}] → {$recipient->email}");
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
            $this->line("  {$flag}  [{$payload['type']}] {$payload['asset_code']} — {$recipient->email} — {$daysLeft}d");
        } else {
            Mail::to($recipient->email)->send(new AssetExpiryReminderMail($mailPayload));
            $this->recordSent($recipient->email, $reminderKey);
            $this->line("  ✓ Sent [{$payload['type']}] {$payload['asset_code']} → {$recipient->email}");
        }

        $this->sent++;
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
