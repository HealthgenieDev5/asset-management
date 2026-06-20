<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `asset_smart_reminders` MODIFY COLUMN `reminder_type` ENUM(
            'warranty','extended_warranty','amc','insurance',
            'puc','fitness','road_tax','service_due',
            'certification','part_warranty','maintenance_schedule','custom'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `asset_smart_reminders` MODIFY COLUMN `reminder_type` ENUM(
            'warranty','extended_warranty','amc','insurance',
            'puc','fitness','road_tax','service_due',
            'certification','part_warranty','custom'
        ) NOT NULL");
    }
};
