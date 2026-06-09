<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->enum('service_type', [
                'preventive_maintenance',
                'corrective_maintenance',
                'inspection',
                'repair',
                'calibration',
                'cleaning',
                'other',
            ])->default('preventive_maintenance');
            $table->date('service_date');
            $table->string('service_agency')->nullable();      // vendor / company
            $table->string('technician_name')->nullable();
            $table->text('work_done')->nullable();
            $table->decimal('service_cost', 15, 2)->nullable();
            $table->string('bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->unsignedSmallInteger('service_interval_value')->nullable();
            $table->enum('service_interval_unit', ['days', 'weeks', 'months', 'years', 'operating_hours', 'kilometers'])->nullable();
            $table->unsignedInteger('meter_reading')->nullable();       // odometer / operating hours
            $table->unsignedInteger('mileage_reading')->nullable();     // km for vehicles
            $table->decimal('downtime_hours', 8, 2)->nullable();
            $table->enum('condition_rating', ['excellent', 'good', 'fair', 'poor', 'critical'])->nullable();
            $table->date('certification_expiry')->nullable();           // for inspection records
            $table->unsignedSmallInteger('certification_reminder_before_days')->nullable();
            $table->unsignedSmallInteger('next_service_reminder_before_days')->nullable();
            $table->text('safety_notes')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'service_date']);
            $table->index('next_service_date');
            $table->index('certification_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_services');
    }
};
