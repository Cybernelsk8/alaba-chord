<?php

namespace Tests\Feature;

use App\Models\Setlist;
use App\Models\Song;
use App\Models\SongLine;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SetlistPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_setlist_pdf_uses_custom_key_and_packs_songs_into_multiple_columns(): void
    {
        $user = User::factory()->create();
        $setlist = Setlist::create([
            'user_id' => $user->id,
            'title' => 'Repertorio de prueba',
            'description' => 'Descripcion de portada',
            'scheduled_at' => '2026-10-02',
        ]);

        for ($songIndex = 1; $songIndex <= 8; $songIndex++) {
            $song = Song::create([
                'user_id' => $user->id,
                'title' => 'Canción ' . $songIndex,
                'artist' => 'Artista de prueba',
                'original_key' => 'C',
            ]);
            $section = SongSection::create([
                'song_id' => $song->id,
                'type' => 'verse',
                'label' => 'Verso',
                'position' => 1,
            ]);
            SongLine::create([
                'song_section_id' => $section->id,
                'position' => 1,
                'type' => 'chord_lyrics',
                'content' => '[C]Línea de prueba ' . $songIndex,
            ]);
            $setlist->songs()->attach($song->id, [
                'position' => $songIndex,
                'custom_key' => 'D',
                'notes' => $songIndex === 1 ? 'Entrada suave' : null,
            ]);
        }

        $response = $this->get(route('setlists.pdf', $setlist));
        $pdf = $response->getContent();

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf);
        preg_match_all('/\/Type\s*\/Page\b/', $pdf, $pageMatches);
        $this->assertSame(3, count($pageMatches[0]));

        $pdftotext = (new ExecutableFinder)->find('pdftotext');
        if ($pdftotext !== null) {
            $pdfPath = tempnam(sys_get_temp_dir(), 'setlist-pdf-');
            file_put_contents($pdfPath, $pdf);

            try {
                $process = new Process([$pdftotext, '-enc', 'UTF-8', '-layout', $pdfPath, '-']);
                $process->run();

                $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
                $text = $process->getOutput();
                $pages = array_values(array_filter(
                    array_map('trim', explode("\f", $text)),
                    fn(string $page) => $page !== '',
                ));

                $this->assertCount(3, $pages);
                $this->assertStringContainsString('Repertorio de prueba', $pages[0]);
                $this->assertStringContainsString('Fecha:', $pages[0]);
                $this->assertStringContainsString('Descripcion de portada', $pages[0]);
                $this->assertStringNotContainsString('Canción 1', $pages[0]);
                $this->assertStringContainsString('Índice de canciones', $pages[1]);
                $this->assertStringContainsString('Canción 1', $pages[1]);
                $this->assertStringNotContainsString('Línea de prueba', $pages[1]);
                $this->assertStringContainsString('Página 3 / 3', $pages[2]);
                $this->assertStringNotContainsString('Repertorio de prueba', $pages[2]);
                $this->assertStringContainsString('Entrada suave', $pages[2]);
                $this->assertStringContainsString('D', $pages[2]);
                $this->assertStringContainsString('prueba 1', $pages[2]);
                $this->assertStringContainsString('prueba 8', $pages[2]);
                $this->assertStringContainsString('Línea de prueba 1', $pages[2]);
            } finally {
                @unlink($pdfPath);
            }
        }
    }
}
