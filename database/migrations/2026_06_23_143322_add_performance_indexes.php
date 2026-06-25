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
        Schema::table('asset_smart_reminders', function (Blueprint $table) {
            $table->index(['remindable_type', 'remindable_id', 'is_active'], 'remindable_active_idx');
        });

        Schema::table('asset_warranties', function (Blueprint $table) {
            $table->index('tracking_mode', 'warranties_tracking_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::table('asset_smart_reminders', function (Blueprint $table) {
            $table->dropIndex('remindable_active_idx');
        });

        Schema::table('asset_warranties', function (Blueprint $table) {
            $table->dropIndex('warranties_tracking_mode_idx');
        });
    }
};
