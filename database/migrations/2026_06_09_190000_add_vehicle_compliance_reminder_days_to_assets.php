<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedSmallInteger('puc_reminder_before_days')->nullable()->after('puc_expiry_date');
            $table->unsignedSmallInteger('fitness_reminder_before_days')->nullable()->after('fitness_expiry_date');
            $table->unsignedSmallInteger('road_tax_reminder_before_days')->nullable()->after('road_tax_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['puc_reminder_before_days', 'fitness_reminder_before_days', 'road_tax_reminder_before_days']);
        });
    }
};
