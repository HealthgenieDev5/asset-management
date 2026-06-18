<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_smart_reminders', function (Blueprint $table) {
            $table->enum('reminder_mode', ['time', 'meter', 'count'])->default('time')->after('reminder_type');
            $table->unsignedInteger('counter_limit')->nullable()->after('reminder_mode');
            $table->string('threshold_unit', 30)->nullable()->after('counter_limit');
            $table->date('expiry_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('asset_smart_reminders', function (Blueprint $table) {
            $table->dropColumn(['reminder_mode', 'counter_limit', 'threshold_unit']);
            $table->date('expiry_date')->nullable(false)->change();
        });
    }
};
