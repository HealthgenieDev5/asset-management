<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_amc_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('contract_number')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_contact_person')->nullable();
            $table->string('vendor_phone', 30)->nullable();
            $table->string('vendor_email')->nullable();
            $table->date('amc_date_from')->nullable();
            $table->date('amc_date_to')->nullable();
            $table->decimal('amc_amount', 15, 2)->nullable();
            $table->string('amc_bill_no')->nullable();
            $table->date('amc_bill_date')->nullable();
            $table->enum('coverage_type', ['comprehensive', 'non_comprehensive', 'parts_only', 'labour_only'])->default('comprehensive');
            $table->text('coverage_details')->nullable();
            $table->text('amc_terms')->nullable();
            $table->unsignedSmallInteger('reminder_before_days')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('amc_date_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_amc_contracts');
    }
};
