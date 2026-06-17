<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_extended_warranties', function (Blueprint $table) {
            $table->unsignedInteger('extended_warranty_counter_limit')->nullable()->after('reminder_before_days');
            $table->unsignedInteger('extended_warranty_reminder_before_units')->nullable()->after('extended_warranty_counter_limit');
        });
    }

    public function down(): void
    {
        Schema::table('asset_extended_warranties', function (Blueprint $table) {
            $table->dropColumn([
                'extended_warranty_counter_limit',
                'extended_warranty_reminder_before_units',
            ]);
        });
    }
};
