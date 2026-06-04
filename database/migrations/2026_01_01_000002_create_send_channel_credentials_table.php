<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('send_channel_credentials', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['email', 'sms', 'viber', 'whatsapp', 'messenger', 'telegram'])->index();
            $table->string('provider')->nullable()->comment('e.g., twilio, nexmo, sendgrid, mailgun');
            $table->string('name')->nullable()->comment('Friendly name for this credential set');
            $table->json('credentials')->comment('Provider-specific credentials (api_key, auth_token, account_sid, etc.)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['channel', 'provider', 'name'], 'send_channel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_channel_credentials');
    }
};
