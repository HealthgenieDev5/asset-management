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
        Schema::table('asset_meter_logs', function (Blueprint $table) {
            $table->string('evidence_path')->nullable()->after('notes');
            $table->string('evidence_original_name')->nullable()->after('evidence_path');
        });
    }

    public function down(): void
    {
        Schema::table('asset_meter_logs', function (Blueprint $table) {
            $table->dropColumn(['evidence_path', 'evidence_original_name']);
        });
    }
};
