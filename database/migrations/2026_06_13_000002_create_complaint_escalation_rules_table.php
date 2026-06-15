<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->foreignId('asset_category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->json('notify_emails');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['location', 'asset_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_escalation_rules');
    }
};
