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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('artist')->nullable();
            $table->string('original_key', 5)->nullable();
            $table->unsignedTinyInteger('capo')->nullable();
            $table->string('time_signature', 10)->nullable(); // {time: 4/4}
            $table->unsignedSmallInteger('tempo')->nullable();
            $table->string('duration', 10)->nullable(); // {duration: 3:45}
            $table->json('meta')->nullable(); // composer, lyricist, copyright, album, year, capo_display...
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
