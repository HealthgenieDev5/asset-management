<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_service_parts', function (Blueprint $table) {
            $table->enum('warranty_tracking_mode', ['time', 'meter', 'count'])->default('time')->nullable()->after('warranty_till');
            $table->string('warranty_unit', 20)->nullable()->after('warranty_tracking_mode');
            $table->enum('warranty_meter_source', ['mileage', 'meter'])->default('meter')->nullable()->after('warranty_unit');
            $table->unsignedInteger('warranty_counter_limit')->nullable()->after('warranty_meter_source');
            $table->unsignedSmallInteger('warranty_reminder_before_days')->nullable()->after('warranty_counter_limit');
            $table->unsignedInteger('warranty_reminder_before_units')->nullable()->after('warranty_reminder_before_days');
        });
    }

    public function down(): void
    {
        Schema::table('asset_service_parts', function (Blueprint $table) {
            $table->dropColumn([
                'warranty_tracking_mode',
                'warranty_unit',
                'warranty_meter_source',
                'warranty_counter_limit',
                'warranty_reminder_before_days',
                'warranty_reminder_before_units',
            ]);
        });
    }
};
