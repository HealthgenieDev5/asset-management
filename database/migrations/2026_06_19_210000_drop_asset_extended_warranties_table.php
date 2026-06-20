<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('asset_extended_warranties');
    }

    public function down(): void
    {
        // Table intentionally not recreated — data was migrated to asset_warranties
    }
};
