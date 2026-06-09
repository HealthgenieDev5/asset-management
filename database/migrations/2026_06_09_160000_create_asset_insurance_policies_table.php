<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('policy_number')->nullable();
            $table->string('insurer_name')->nullable();
            $table->string('insurer_contact_person')->nullable();
            $table->string('insurer_phone', 30)->nullable();
            $table->string('insurer_email')->nullable();
            $table->string('policy_type')->nullable();
            $table->date('policy_date_from')->nullable();
            $table->date('policy_date_to')->nullable();
            $table->decimal('premium_amount', 15, 2)->nullable();
            $table->decimal('sum_insured', 15, 2)->nullable();
            $table->string('bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->text('coverage_details')->nullable();
            $table->unsignedSmallInteger('reminder_before_days')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('policy_date_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_insurance_policies');
    }
};
