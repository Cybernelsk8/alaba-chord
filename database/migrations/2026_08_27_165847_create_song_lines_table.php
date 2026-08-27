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
        Schema::create('song_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_section_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->enum('type', ['chord_lyrics', 'comment', 'tab_line', 'directive_raw'])->default('chord_lyrics');
            $table->text('content'); // "Te quie[G]ro..." o texto verbatim si es tab/comment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_lines');
    }
};
