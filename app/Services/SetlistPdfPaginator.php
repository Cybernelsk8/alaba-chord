<?php

namespace App\Services;

use App\Models\Song;

/**
 * @phpstan-type PdfLine array{type: string, content?: string, html?: string}
 * @phpstan-type PdfSection array{label: string, lines: list<PdfLine>}
 * @phpstan-type PdfSong array{song: Song, htmlContent: list<PdfSection>, customKey: ?string, notes: ?string}
 * @phpstan-type PdfBlock array{songNumber: int, title: string, artist: ?string, key: string, capo: int|null, notes: ?string, startsSong: bool, sectionLabel: ?string, lines: list<PdfLine>, estimatedHeight: int}
 * @phpstan-type PdfColumns array{0: list<PdfBlock>, 1: list<PdfBlock>}
 * @phpstan-type PdfPage array{pageNumber: int, columns: PdfColumns}
 */
class SetlistPdfPaginator
{
    private const PAGE_CONTENT_HEIGHT = 760;

    private const PAGE_HEADER_HEIGHT = 32;

    private const MAX_BLOCK_HEIGHT = 560;

    /**
     * @param  list<PdfSong>  $songsData
     * @return list<PdfPage>
     */
    public static function paginate(array $songsData): array
    {
        $pages = [];
        $pageNumber = 1;
        $columns = [[], []];
        $columnHeights = [0, 0];
        $columnIndex = 0;
        foreach ($songsData as $songIndex => $songData) {
            foreach (self::blocksForSong($songData, $songIndex + 1) as $block) {
                while (true) {
                    $capacity = self::PAGE_CONTENT_HEIGHT - self::PAGE_HEADER_HEIGHT;

                    if ($capacity >= $columnHeights[$columnIndex] + $block['estimatedHeight']) {
                        if ($columnIndex === 0) {
                            $columns[0][] = $block;
                            $columnHeights[0] += $block['estimatedHeight'];
                        } else {
                            $columns[1][] = $block;
                            $columnHeights[1] += $block['estimatedHeight'];
                        }
                        break;
                    }

                    if ($columnIndex === 0) {
                        $columnIndex = 1;

                        continue;
                    }

                    $pages[] = self::newPage($pageNumber, $columns);
                    $pageNumber++;
                    $columns = [[], []];
                    $columnHeights = [0, 0];
                    $columnIndex = 0;
                }
            }
        }

        $pages[] = self::newPage($pageNumber, $columns);

        return $pages;
    }

    /**
     * @param  PdfColumns  $columns
     * @return PdfPage
     */
    private static function newPage(int $pageNumber, array $columns): array
    {
        return ['pageNumber' => $pageNumber, 'columns' => $columns];
    }

    /**
     * @param  PdfSong  $songData
     * @return list<PdfBlock>
     */
    private static function blocksForSong(array $songData, int $songNumber): array
    {
        $blocks = [];
        $startsSong = true;
        $song = $songData['song'];

        foreach ($songData['htmlContent'] as $section) {
            $lines = $section['lines'];
            $currentLines = [];
            $currentHeight = self::headingHeight($songData, $startsSong) + 18;

            foreach ($lines as $line) {
                $lineHeight = self::lineHeight($line);

                if ($currentLines !== [] && $currentHeight + $lineHeight > self::MAX_BLOCK_HEIGHT) {
                    $blocks[] = self::makeBlock($songData, $songNumber, $section['label'], $currentLines, $startsSong);
                    $startsSong = false;
                    $currentLines = [];
                    $currentHeight = self::headingHeight($songData, false) + 18;
                }

                $currentLines[] = $line;
                $currentHeight += $lineHeight;
            }

            $blocks[] = self::makeBlock($songData, $songNumber, $section['label'], $currentLines, $startsSong);
            $startsSong = false;
        }

        if ($songData['htmlContent'] === []) {
            $blocks[] = self::makeBlock($songData, $songNumber, null, [], true);
        }

        return $blocks;
    }

    /**
     * @param  PdfSong  $songData
     * @param  list<PdfLine>  $lines
     * @return PdfBlock
     */
    private static function makeBlock(array $songData, int $songNumber, ?string $sectionLabel, array $lines, bool $startsSong): array
    {
        $song = $songData['song'];
        $estimatedHeight = self::headingHeight($songData, $startsSong) + ($sectionLabel !== null ? 18 : 0);
        foreach ($lines as $line) {
            $estimatedHeight += self::lineHeight($line);
        }

        return [
            'songNumber' => $songNumber,
            'title' => (string) $song->title,
            'artist' => $song->artist,
            'key' => $songData['customKey'] ?: ($song->original_key ?: 'C'),
            'capo' => $song->capo,
            'notes' => $startsSong ? $songData['notes'] : null,
            'startsSong' => $startsSong,
            'sectionLabel' => $sectionLabel,
            'lines' => $lines,
            'estimatedHeight' => min($estimatedHeight, self::MAX_BLOCK_HEIGHT),
        ];
    }

    /**
     * @param  PdfSong  $songData
     */
    private static function headingHeight(array $songData, bool $startsSong): int
    {
        if (! $startsSong) {
            return 24;
        }

        $notes = $songData['notes'] ?? '';
        $noteLines = $notes === '' ? 0 : (int) ceil(mb_strlen($notes) / 55);

        return 38 + ($noteLines * 10);
    }

    /** @param PdfLine $line */
    private static function lineHeight(array $line): int
    {
        $content = $line['content'] ?? strip_tags($line['html'] ?? '');
        if ($line['type'] === 'chord_lyrics') {
            $content = preg_replace('/\[[^\]]+\]/', '', $content) ?? $content;
        }

        $charactersPerLine = $line['type'] === 'tab_line' ? 80 : 52;
        $wrappedLines = max(1, (int) ceil(mb_strlen($content) / $charactersPerLine));

        return 4 + ($wrappedLines * 10);
    }
}
