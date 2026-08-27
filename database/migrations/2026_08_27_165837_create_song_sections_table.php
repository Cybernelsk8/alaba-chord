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
        Schema::create('song_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['verse', 'chorus', 'bridge', 'pre_chorus', 'tab', 'grid', 'intro', 'outro', 'instrumental', 'other']);
            $table->string('label')->nullable(); // override visual, ej "Coro 2"
            $table->unsignedInteger('position');
            $table->foreignId('repeats_section_id')->nullable()->constrained('song_sections')->nullOnDelete(); // para {chorus} shorthand
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_sections');
    }
};
