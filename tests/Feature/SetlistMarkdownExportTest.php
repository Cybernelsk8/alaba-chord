<?php

namespace Tests\Feature;

use App\Models\Setlist;
use App\Models\Song;
use App\Models\SongLine;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetlistMarkdownExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_markdown_download_is_single_column_and_transposes_each_song_to_its_setlist_key(): void
    {
        $user = User::factory()->create();
        $setlist = Setlist::create([
            'user_id' => $user->id,
            'title' => 'Ensayo semanal',
            'description' => 'Repertorio en orden',
            'scheduled_at' => '2026-10-02',
        ]);

        $firstSong = $this->createSong($user->id, 'Primera canción', 'C', 'Coro', '[C]Hola [G]mundo');
        $secondSong = $this->createSong($user->id, 'Segunda canción', 'Am', 'Verso', '[Am]Sigo [E]cantando');

        $setlist->songs()->attach($firstSong->id, [
            'position' => 1,
            'custom_key' => 'D',
            'notes' => 'Entrada suave',
        ]);
        $setlist->songs()->attach($secondSong->id, [
            'position' => 2,
            'custom_key' => 'Bm',
            'notes' => null,
        ]);

        $response = $this->get(route('setlists.markdown', $setlist));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/markdown; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="ensayo-semanal-cancionero.md"');
        $response->assertSeeText('**Fecha:** 02/10/2026');
        $response->assertSeeText('Repertorio en orden');
        $response->assertSeeText('## 1. Primera canción');
        $response->assertSeeText('**Tono:** D');
        $response->assertSeeText('**Nota para la banda:** Entrada suave');
        $response->assertSeeText('Hola');
        $response->assertSeeText('mundo');
        $response->assertSeeText('---');
        $response->assertSeeText('## 2. Segunda canción');
        $response->assertSeeText('Sigo');
        $response->assertSeeText('cantando');

        $content = $response->getContent();
        $this->assertStringContainsString(
            '<span style="font-family: monospace; white-space: pre;"><strong>D</strong>    <strong>A</strong><br>Hola mundo</span>',
            $content,
        );
        $this->assertStringContainsString(
            '<span style="font-family: monospace; white-space: pre;"><strong>Bm</strong>   <strong>F#</strong><br>Sigo cantando</span>',
            $content,
        );
        $this->assertStringNotContainsString('<table', $content);
        $this->assertStringNotContainsString('<td', $content);
        $this->assertLessThan(strpos($content, 'Hola'), strpos($content, '<strong>D</strong>'));
        $this->assertStringNotContainsString('[D]Hola', $content);
    }

    private function createSong(int $userId, string $title, string $key, string $sectionLabel, string $lineContent): Song
    {
        $song = Song::create([
            'user_id' => $userId,
            'title' => $title,
            'artist' => 'Artista',
            'original_key' => $key,
        ]);
        $section = SongSection::create([
            'song_id' => $song->id,
            'type' => $sectionLabel === 'Coro' ? 'chorus' : 'verse',
            'label' => $sectionLabel,
            'position' => 1,
        ]);
        SongLine::create([
            'song_section_id' => $section->id,
            'position' => 1,
            'type' => 'chord_lyrics',
            'content' => $lineContent,
        ]);

        return $song;
    }
}
