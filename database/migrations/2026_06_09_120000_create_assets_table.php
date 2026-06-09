<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 20)->unique();
            $table->string('asset_name');
            $table->text('asset_description')->nullable();
            $table->foreignId('asset_category_id')->constrained('asset_categories');
            $table->foreignId('asset_subcategory_id')->nullable()->constrained('asset_subcategories');
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->smallInteger('model_year')->nullable();
            $table->string('location')->nullable();
            $table->string('department')->nullable();
            $table->string('custodian')->nullable();
            $table->string('vendor_supplier')->nullable();
            $table->string('bill_no')->nullable();
            $table->decimal('bill_amount', 15, 2)->nullable();
            $table->date('bill_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('warranty_details')->nullable();
            $table->date('warranty_lapse_date')->nullable();
            $table->unsignedSmallInteger('warranty_reminder_before_days')->nullable();
            $table->enum('maintenance_schedule_type', ['date_based', 'hours_based', 'mileage_based', 'custom', 'none'])->default('none');
            $table->unsignedSmallInteger('maintenance_interval_value')->nullable();
            $table->enum('maintenance_interval_unit', ['days', 'weeks', 'months', 'years', 'operating_hours', 'miles', 'kilometers'])->nullable();
            $table->boolean('inspection_required')->default(false);
            $table->unsignedSmallInteger('inspection_frequency_value')->nullable();
            $table->enum('inspection_frequency_unit', ['days', 'weeks', 'months', 'years'])->nullable();
            $table->date('puc_expiry_date')->nullable();
            $table->date('fitness_expiry_date')->nullable();
            $table->date('road_tax_expiry_date')->nullable();
            $table->decimal('vehicle_obv', 15, 2)->nullable();
            $table->decimal('vehicle_depreciation_percent', 5, 2)->nullable();
            $table->decimal('vehicle_depreciation_book_value', 15, 2)->nullable();
            $table->enum('status', ['active', 'under_repair', 'disposed', 'scrapped', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
