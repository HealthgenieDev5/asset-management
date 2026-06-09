<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_service_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_service_id')->constrained('asset_services')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets');
            $table->string('part_name');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('part_cost', 15, 2)->nullable();
            $table->string('purchased_from')->nullable();
            $table->date('warranty_till')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['asset_service_id']);
            $table->index(['asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_service_parts');
    }
};
