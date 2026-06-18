<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            $table->enum('service_type', [
                'preventive_maintenance',
                'corrective_maintenance',
                'inspection',
                'repair',
                'calibration',
                'cleaning',
                'other',
            ])->nullable()->after('schedule_category');

            $table->index('service_type');
        });
    }

    public function down(): void
    {
        Schema::table('asset_maintenance_schedules', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn('service_type');
        });
    }
};
