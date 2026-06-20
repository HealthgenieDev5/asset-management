<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->enum('event', ['created', 'updated', 'deleted', 'restored']);
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['asset_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('causer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_audit_logs');
    }
};
