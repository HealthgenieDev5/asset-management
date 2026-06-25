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
        Schema::table('asset_service_parts', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('purchased_from')->constrained('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_service_parts', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Vendor::class);
            $table->dropColumn('vendor_id');
        });
    }
};
