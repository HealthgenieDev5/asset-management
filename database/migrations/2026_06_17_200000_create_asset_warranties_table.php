<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('warranty_type', ['original', 'extended'])->default('original');
            $table->enum('scope', ['overall', 'part'])->default('overall');
            $table->string('part_name', 100)->nullable();
            $table->string('part_serial_number', 100)->nullable();
            $table->string('vendor', 255)->nullable();
            $table->string('bill_no', 255)->nullable();
            $table->decimal('bill_amount', 15, 2)->nullable();
            $table->text('details')->nullable();
            $table->text('terms')->nullable();
            $table->enum('tracking_mode', ['time', 'meter', 'count'])->default('time');
            $table->string('unit', 20)->nullable();
            $table->enum('meter_source', ['mileage', 'meter'])->nullable()->default('meter');
            $table->date('date_from')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedSmallInteger('reminder_before_days')->nullable();
            $table->unsignedInteger('counter_limit')->nullable();
            $table->unsignedInteger('reminder_before_units')->nullable();
            $table->enum('status', ['active', 'disposed', 'expired'])->default('active');
            $table->date('disposed_at')->nullable();
            $table->string('disposed_reason', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Migrate existing original warranty data from assets table
        DB::table('assets')
            ->whereNotNull('deleted_at')
            ->orWhereNull('deleted_at')
            ->get(['id', 'warranty_details', 'warranty_lapse_date', 'warranty_reminder_before_days',
                   'warranty_tracking_mode', 'warranty_unit', 'warranty_meter_source',
                   'warranty_counter_limit', 'warranty_reminder_before_units', 'created_by'])
            ->each(function ($asset) {
                $hasData = $asset->warranty_lapse_date
                    || $asset->warranty_counter_limit
                    || $asset->warranty_details;

                if (! $hasData) {
                    return;
                }

                DB::table('asset_warranties')->insert([
                    'asset_id'              => $asset->id,
                    'warranty_type'         => 'original',
                    'scope'                 => 'overall',
                    'details'               => $asset->warranty_details,
                    'tracking_mode'         => $asset->warranty_tracking_mode ?? 'time',
                    'unit'                  => $asset->warranty_unit,
                    'meter_source'          => $asset->warranty_meter_source ?? 'meter',
                    'expiry_date'           => $asset->warranty_lapse_date,
                    'reminder_before_days'  => $asset->warranty_reminder_before_days,
                    'counter_limit'         => $asset->warranty_counter_limit,
                    'reminder_before_units' => $asset->warranty_reminder_before_units,
                    'status'                => 'active',
                    'created_by'            => $asset->created_by,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            });

        // Migrate extended warranties
        DB::table('asset_extended_warranties')->get()->each(function ($ew) {
            $hasData = $ew->extended_warranty_date_to
                || $ew->extended_warranty_counter_limit
                || $ew->extended_warranty_vendor;

            if (! $hasData) {
                return;
            }

            DB::table('asset_warranties')->insert([
                'asset_id'              => $ew->asset_id,
                'warranty_type'         => 'extended',
                'scope'                 => 'overall',
                'vendor'                => $ew->extended_warranty_vendor,
                'bill_no'               => $ew->extended_warranty_bill_no,
                'bill_amount'           => $ew->extended_warranty_amount,
                'terms'                 => $ew->extended_warranty_terms,
                'tracking_mode'         => $ew->ew_tracking_mode ?? 'time',
                'unit'                  => $ew->ew_unit,
                'meter_source'          => $ew->ew_meter_source ?? 'meter',
                'date_from'             => $ew->extended_warranty_date_from,
                'expiry_date'           => $ew->extended_warranty_date_to,
                'reminder_before_days'  => $ew->reminder_before_days,
                'counter_limit'         => $ew->extended_warranty_counter_limit,
                'reminder_before_units' => $ew->extended_warranty_reminder_before_units,
                'remarks'               => $ew->remarks,
                'status'                => 'active',
                'created_by'            => $ew->created_by ?? null,
                'created_at'            => $ew->created_at,
                'updated_at'            => $ew->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_warranties');
    }
};
