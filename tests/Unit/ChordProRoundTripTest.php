<?php

namespace Tests\Unit;

use App\Models\Song;
use App\Models\SongLine;
use App\Models\SongSection;
use App\Services\ChordDiagramService;
use App\Services\ChordPro\ChordCatalog;
use App\Services\ChordPro\ChordProExporter;
use App\Services\ChordPro\ChordProImporter;
use App\Services\ChordPro\ChordProLine;
use App\Services\ChordPro\ChordTransposer;
use PHPUnit\Framework\TestCase;

class ChordProRoundTripTest extends TestCase
{
    public function test_parser_keeps_directives_section_types_comments_and_line_spacing(): void
    {
        $parsed = ChordProImporter::parse(implode("\n", [
            '{t: Canción de prueba}',
            '{key: Bb}',
            '{start_of_verse: Verso 1}',
            '  Te [Bb]canto  ',
            '{c: Indicación}',
            '{end_of_verse}',
            '{sot: Riff}',
            'e|---|',
            '{eot}',
        ]));

        $this->assertSame('Canción de prueba', $parsed['song']['title']);
        $this->assertSame('Bb', $parsed['song']['original_key']);
        $this->assertSame('verse', $parsed['sections'][0]['type']);
        $this->assertSame('Verso 1', $parsed['sections'][0]['label']);
        $this->assertSame('  Te [Bb]canto  ', $parsed['sections'][0]['lines'][0]['content']);
        $this->assertSame('comment', $parsed['sections'][0]['lines'][1]['type']);
        $this->assertSame('tab', $parsed['sections'][1]['type']);
        $this->assertSame('tab_line', $parsed['sections'][1]['lines'][0]['type']);
    }

    public function test_song_export_and_parse_preserve_song_structure(): void
    {
        $verse = new SongSection(['type' => 'verse', 'label' => 'Verso 1']);
        $verse->setRelation('lines', collect([
            new SongLine(['type' => 'chord_lyrics', 'content' => '  Te [Bb]canto  ']),
            new SongLine(['type' => 'comment', 'content' => 'Entrada suave']),
        ]));

        $tab = new SongSection(['type' => 'tab', 'label' => 'Riff']);
        $tab->setRelation('lines', collect([
            new SongLine(['type' => 'tab_line', 'content' => 'e|---|']),
        ]));

        $song = new Song([
            'title' => 'Canción de prueba',
            'artist' => 'Artista',
            'original_key' => 'Bb',
            'tempo' => 92,
            'meta' => ['copyright' => '2026'],
        ]);
        $song->setRelation('sections', collect([$verse, $tab]));

        $parsed = ChordProImporter::parse(ChordProExporter::export($song));

        $this->assertSame('Canción de prueba', $parsed['song']['title']);
        $this->assertSame('Artista', $parsed['song']['artist']);
        $this->assertSame('Bb', $parsed['song']['original_key']);
        $this->assertSame(92, $parsed['song']['tempo']);
        $this->assertSame('2026', $parsed['song']['meta']['copyright']);
        $this->assertSame('  Te [Bb]canto  ', $parsed['sections'][0]['lines'][0]['content']);
        $this->assertSame('comment', $parsed['sections'][0]['lines'][1]['type']);
        $this->assertSame('tab_line', $parsed['sections'][1]['lines'][0]['type']);
    }

    public function test_available_chords_have_guitar_diagrams(): void
    {
        $this->assertCount(442, ChordCatalog::chordSymbols());
        $this->assertContains('F#sus4', ChordCatalog::chordSymbols());
        $this->assertContains('Bbm7b5', ChordCatalog::chordSymbols());
        $availableChords = ChordDiagramService::availableChords();
        $this->assertContains('C#m7', $availableChords);
        $this->assertContains('Dbmaj7', $availableChords);
        $this->assertLessThan(count(ChordCatalog::chordSymbols()), count($availableChords));

        foreach ($availableChords as $chord) {
            $this->assertNotSame('', ChordDiagramService::getSvg($chord), $chord);
        }

        $this->assertNotSame('', ChordDiagramService::getSvg('Dbmaj7'));
        $this->assertSame('', ChordDiagramService::getSvg('C13'));
    }

    public function test_transposition_is_shared_and_preserves_slash_basses_and_key_spelling(): void
    {
        $this->assertSame('Bb/D', ChordTransposer::transposeChord('A/C#', 1, 'Bb'));
        $this->assertSame('D7sus4', ChordTransposer::transposeChord('C7sus4', 2));
        $this->assertSame('[B/D#] y [N.C.]', ChordProLine::transpose('[A/C#] y [N.C.]', 2));
        $this->assertSame(-1, ChordTransposer::semitonesBetweenKeys('C', 'B'));
    }

    public function test_key_inference_supports_a_bare_chord(): void
    {
        $this->assertSame('C', ChordTransposer::inferKeyFromContent('[C] Una línea'));
    }

    public function test_chord_line_extraction_reports_positions_without_chord_tokens(): void
    {
        $this->assertSame([
            ['chord' => 'C', 'position' => 0],
            ['chord' => 'G', 'position' => 3],
        ], ChordProLine::extractChords('[C]Te [G]quiero'));
    }
}
