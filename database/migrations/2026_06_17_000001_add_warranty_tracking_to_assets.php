<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->enum('warranty_tracking_mode', ['time', 'meter', 'count'])->default('time')->after('warranty_reminder_before_days');
            $table->string('warranty_unit', 20)->nullable()->after('warranty_tracking_mode');
            $table->enum('warranty_meter_source', ['mileage', 'meter'])->default('meter')->after('warranty_unit');
            $table->unsignedInteger('warranty_counter_limit')->nullable()->after('warranty_meter_source');
            $table->unsignedInteger('warranty_reminder_before_units')->nullable()->after('warranty_counter_limit');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'warranty_tracking_mode',
                'warranty_unit',
                'warranty_meter_source',
                'warranty_counter_limit',
                'warranty_reminder_before_units',
            ]);
        });
    }
};
