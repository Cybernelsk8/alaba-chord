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
        Schema::create('setlist_song', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('song_id')->constrained()->onDelete('cascade');
            $table->integer('position')->default(1);
            $table->string('custom_key')->nullable(); // Tono específico para este setlist
            $table->text('notes')->nullable(); // Ej. "Entrada suave", "Solo de guitarra"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlist_song');
    }
};
