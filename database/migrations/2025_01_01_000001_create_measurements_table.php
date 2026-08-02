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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->string('perspective');
            $table->string('objective');
            $table->string('measurement');
            $table->text('definition')->nullable();
            $table->string('formula')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2)->default(0);
            $table->timestamps();

            $table->index('perspective');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
