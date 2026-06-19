<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Restore asset_service_parts: drop asset_part_id FK, add part_name + part_serial_number
        Schema::table('asset_service_parts', function (Blueprint $table) {
            if (Schema::hasColumn('asset_service_parts', 'asset_part_id')) {
                $table->dropForeign(['asset_part_id']);
                $table->dropColumn('asset_part_id');
            }
            if (! Schema::hasColumn('asset_service_parts', 'part_name')) {
                $table->string('part_name')->after('asset_id');
            }
            if (! Schema::hasColumn('asset_service_parts', 'part_serial_number')) {
                $table->string('part_serial_number')->nullable()->after('part_name');
            }
        });

        // Restore asset_warranties: drop asset_part_id FK, add part_name + part_serial_number
        if (Schema::hasTable('asset_warranties')) {
            Schema::table('asset_warranties', function (Blueprint $table) {
                if (Schema::hasColumn('asset_warranties', 'asset_part_id')) {
                    $table->dropForeign(['asset_part_id']);
                    $table->dropColumn('asset_part_id');
                }
                if (! Schema::hasColumn('asset_warranties', 'part_name')) {
                    $table->string('part_name')->nullable()->after('scope');
                }
                if (! Schema::hasColumn('asset_warranties', 'part_serial_number')) {
                    $table->string('part_serial_number')->nullable()->after('part_name');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('asset_service_parts', function (Blueprint $table) {
            $table->dropColumn(['part_name', 'part_serial_number']);
            $table->foreignId('asset_part_id')->nullable()->after('asset_id')->nullOnDelete();
        });

        if (Schema::hasTable('asset_warranties')) {
            Schema::table('asset_warranties', function (Blueprint $table) {
                $table->dropColumn(['part_name', 'part_serial_number']);
                $table->foreignId('asset_part_id')->nullable()->after('scope')->nullOnDelete();
            });
        }
    }
};
