<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_extended_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('extended_warranty_vendor')->nullable();
            $table->date('extended_warranty_date_from')->nullable();
            $table->date('extended_warranty_date_to')->nullable();
            $table->string('extended_warranty_bill_no')->nullable();
            $table->decimal('extended_warranty_amount', 15, 2)->nullable();
            $table->text('extended_warranty_terms')->nullable();
            $table->unsignedSmallInteger('reminder_before_days')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_extended_warranties');
    }
};
