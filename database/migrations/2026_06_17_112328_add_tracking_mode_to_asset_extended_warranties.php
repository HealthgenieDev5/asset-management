<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_extended_warranties', function (Blueprint $table) {
            $table->enum('ew_tracking_mode', ['time', 'meter', 'count'])->default('time')->after('extended_warranty_reminder_before_units');
            $table->string('ew_unit', 20)->nullable()->after('ew_tracking_mode');
            $table->enum('ew_meter_source', ['mileage', 'meter'])->default('meter')->after('ew_unit');
        });
    }

    public function down(): void
    {
        Schema::table('asset_extended_warranties', function (Blueprint $table) {
            $table->dropColumn(['ew_tracking_mode', 'ew_unit', 'ew_meter_source']);
        });
    }
};
