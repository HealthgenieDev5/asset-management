<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();

            // Snapshot fields copied from asset at complaint creation time
            $table->string('location')->nullable();
            $table->string('department')->nullable();
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('asset_subcategory_id')->nullable()->constrained('asset_subcategories')->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('reported_by_name');
            $table->string('reported_by_email')->nullable();
            $table->string('reported_by_phone', 30)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'acknowledged', 'in_progress', 'resolved', 'closed', 'rejected'])->default('open');
            $table->text('resolution_summary')->nullable();
            $table->date('resolved_at')->nullable();

            // Optional link to a service entry (set when complaint is linked to a repair)
            $table->foreignId('asset_service_id')->nullable()->constrained('asset_services')->nullOnDelete();

            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'status']);
            $table->index(['asset_id', 'priority']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_complaints');
    }
};
