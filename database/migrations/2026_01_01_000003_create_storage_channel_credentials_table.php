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
        Schema::create('storage_channel_credentials', function (Blueprint $table) {
            $table->id();
            $table->enum('storage', ['eloquent', 'mongodb', 'google_drive', 'excel', 'custom'])->index();
            $table->string('name')->nullable()->comment('Friendly name for this configuration');
            $table->string('connection_name')->nullable()->comment('Database connection name for eloquent (e.g., "mysql", "pgsql", "sqlite")');
            $table->json('connection_details')->comment('Connection config: host, port, username, password, database, etc.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['storage', 'connection_name', 'name'], 'storage_channel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_channel_credentials');
    }
};
