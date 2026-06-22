<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['service_types', 'sla_response_hours', 'sla_resolution_days', 'contact_person', 'notes']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->enum('type', ['company', 'individual'])->default('company')->after('code');
            $table->string('alt_phone', 30)->nullable()->after('phone');
            $table->string('alt_email', 255)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['type', 'alt_phone', 'alt_email']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->json('service_types')->nullable();
            $table->unsignedSmallInteger('sla_response_hours')->nullable();
            $table->unsignedSmallInteger('sla_resolution_days')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('notes')->nullable();
        });
    }
};
