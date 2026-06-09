<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_reminder_email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('reminder_key'); // e.g. "warranty:42", "puc:7", "amc:3"
            $table->date('sent_date');
            $table->timestamp('created_at')->useCurrent();

            // Prevent duplicate sends on the same day for the same recipient+key
            $table->unique(['recipient_email', 'reminder_key', 'sent_date'], 'reminder_email_log_unique');
            $table->index(['sent_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_reminder_email_logs');
    }
};
