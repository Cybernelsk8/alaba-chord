<?php

namespace Tests\Unit;

use App\Models\Song;
use App\Services\SetlistPdfPaginator;
use PHPUnit\Framework\TestCase;

class SetlistPdfPaginatorTest extends TestCase
{
    public function test_left_column_is_filled_before_song_blocks_move_right(): void
    {
        $songs = [];
        for ($songIndex = 1; $songIndex <= 4; $songIndex++) {
            $songs[] = $this->songData('Canción ' . $songIndex, 8);
        }

        $pages = SetlistPdfPaginator::paginate($songs);

        $this->assertCount(1, $pages);
        $this->assertSame(4, count($pages[0]['columns'][0]));
        $this->assertSame(0, count($pages[0]['columns'][1]));
        $this->assertSame(1, $pages[0]['columns'][0][0]['songNumber']);
        $this->assertSame(4, $pages[0]['columns'][0][3]['songNumber']);
    }

    public function test_long_sections_are_split_into_continuations_without_losing_lines(): void
    {
        $songData = $this->songData('Canción extensa', 100);
        $pages = SetlistPdfPaginator::paginate([$songData]);
        $blocks = [];
        foreach ($pages as $page) {
            $blocks = array_merge($blocks, ...$page['columns']);
        }
        $renderedLines = array_merge(...array_map(
            fn(array $block) => array_column($block['lines'], 'content'),
            $blocks,
        ));

        $this->assertGreaterThan(1, count($blocks));
        $this->assertSame(array_column($songData['htmlContent'][0]['lines'], 'content'), $renderedLines);
        $this->assertTrue($blocks[0]['startsSong']);
        $this->assertFalse($blocks[1]['startsSong']);
    }

    /** @return array{song: Song, htmlContent: list<array{label: string, lines: list<array{type: string, content: string}>}>, customKey: string|null, notes: string|null} */
    private function songData(string $title, int $lineCount): array
    {
        $lines = [];
        for ($lineIndex = 1; $lineIndex <= $lineCount; $lineIndex++) {
            $lines[] = [
                'type' => 'chord_lyrics',
                'content' => 'Verso [C]linea ' . $lineIndex . ' con letra',
            ];
        }

        return [
            'song' => new Song(['title' => $title, 'artist' => 'Artista']),
            'htmlContent' => [['label' => 'Verso', 'lines' => $lines]],
            'customKey' => 'C',
            'notes' => null,
        ];
    }
}
