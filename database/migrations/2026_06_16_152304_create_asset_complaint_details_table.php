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
        Schema::create('asset_complaint_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_complaint_id')->constrained('asset_complaints')->cascadeOnDelete();
            $table->string('label');
            $table->string('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_complaint_details');
    }
};
