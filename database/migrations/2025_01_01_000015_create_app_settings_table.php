<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores editable runtime configuration (e.g. Gemini API key) outside
     * the .env file so the value can be updated from the UI on shared
     * hosting where .env is read-only and config:cache is enabled. The
     * `value` column is encrypted via the model's `encrypted` cast, so the
     * secret is never stored in plaintext inside the database or backups.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};