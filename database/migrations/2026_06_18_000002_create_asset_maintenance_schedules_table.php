<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('schedule_category', ['repair', 'servicing']);
            $table->string('schedule_name');
            $table->text('description')->nullable();
            $table->enum('schedule_type', ['date', 'mileage', 'operating_hours'])->default('date');

            // Date-based fields
            $table->unsignedSmallInteger('interval_value')->nullable();
            $table->enum('interval_unit', ['days', 'weeks', 'months', 'years'])->nullable();
            $table->date('last_done_date')->nullable();
            $table->date('next_due_date')->nullable();

            // Mileage-based fields
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedInteger('last_done_km')->nullable();

            // Operating hours-based fields
            $table->unsignedInteger('interval_hours')->nullable();
            $table->unsignedInteger('last_done_hours')->nullable();

            // Multi-threshold reminders
            $table->json('reminder_thresholds');
            $table->enum('reminder_unit', ['days', 'km', 'hours'])->default('days');

            $table->boolean('is_active')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'is_active']);
            $table->index('schedule_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_schedules');
    }
};
