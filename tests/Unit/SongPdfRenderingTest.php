<?php

namespace Tests\Unit;

use App\Http\Controllers\SongPdfController;
use App\Models\Song;
use App\Models\SongLine;
use App\Models\SongSection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SongPdfRenderingTest extends TestCase
{
    public function test_lyrics_in_chorus_sections_are_rendered_bold(): void
    {
        $song = new Song(['title' => 'Coro']);
        $section = new SongSection(['type' => 'chorus', 'label' => 'Coro']);
        $section->setRelation('lines', collect([
            new SongLine(['type' => 'chord_lyrics', 'content' => '[C]Letra del coro']),
        ]));
        $song->setRelation('sections', collect([$section]));

        $renderSongHtml = new ReflectionMethod(SongPdfController::class, 'renderSongHtml');
        $renderedSections = $renderSongHtml->invoke(new SongPdfController, $song, 0, null, true);

        $this->assertSame('chorus', $renderedSections[0]['type']);
        $this->assertStringContainsString('<strong>Letra del coro</strong>', $renderedSections[0]['lines'][0]['html']);
    }

    public function test_lyrics_outside_chorus_are_not_bolded(): void
    {
        $song = new Song(['title' => 'Verso']);
        $section = new SongSection(['type' => 'verse', 'label' => 'Verso']);
        $section->setRelation('lines', collect([
            new SongLine(['type' => 'chord_lyrics', 'content' => '[C]Letra del verso']),
        ]));
        $song->setRelation('sections', collect([$section]));

        $renderSongHtml = new ReflectionMethod(SongPdfController::class, 'renderSongHtml');
        $renderedSections = $renderSongHtml->invoke(new SongPdfController, $song, 0, null, true);

        $this->assertStringNotContainsString('<strong>Letra del verso</strong>', $renderedSections[0]['lines'][0]['html']);
    }
}
