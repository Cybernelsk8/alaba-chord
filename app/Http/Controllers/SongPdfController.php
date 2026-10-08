<?php

namespace App\Http\Controllers;

use App\Models\Setlist;
use App\Models\Song;
use App\Services\ChordPro\ChordTransposer;
use App\Services\SetlistPdfPaginator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SongPdfController extends Controller
{
    /**
     * Exporta una canción individual a PDF con transposición opcional.
     */
    public function exportSong(Request $request, Song $song): Response
    {
        // Obtiene los semitonos de la Query String (ej. ?semitones=2), por defecto 0
        $semitones = (int) $request->query('semitones', 0);

        $song->load('sections.lines');

        $htmlContent = $this->renderSongHtml($song, $semitones);

        $pdf = Pdf::loadView('pdf.song', [
            'song' => $song,
            'htmlContent' => $htmlContent,
            'semitones' => $semitones,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("{$song->title}.pdf");
    }

    /**
     * Exporta un repertorio completo a PDF en un solo archivo.
     */
    public function exportSetlist(Setlist $setlist): Response
    {
        $setlist->load('songs.sections.lines');

        $songsData = [];
        foreach ($setlist->songs as $song) {
            $originalKey = $song->original_key ?? $this->inferSongKey($song);
            $pivot = $song->getRelation('pivot');
            $customKey = data_get($pivot, 'custom_key');
            $targetKey = is_string($customKey) && $customKey !== '' ? $customKey : $originalKey;
            $semitones = ChordTransposer::semitonesBetweenKeys($originalKey, $targetKey);
            $notes = data_get($pivot, 'notes');

            $songsData[] = [
                'song' => $song,
                'htmlContent' => $this->renderSongHtml($song, $semitones, $targetKey, true),
                'customKey' => $targetKey,
                'notes' => is_string($notes) ? $notes : null,
            ];
        }

        $indexRows = array_chunk(array_map(
            fn(int $index, array $item) => [
                'number' => $index + 1,
                'song' => $item['song'],
                'key' => $item['customKey'],
            ],
            array_keys($songsData),
            $songsData,
        ), 2);
        $indexPages = array_chunk($indexRows, 50);
        $songPages = $songsData === [] ? [] : SetlistPdfPaginator::paginate($songsData);
        $pages = [['type' => 'cover', 'pageNumber' => 1]];

        foreach ($indexPages as $rows) {
            $pages[] = [
                'type' => 'index',
                'pageNumber' => count($pages) + 1,
                'rows' => $rows,
            ];
        }

        foreach ($songPages as $songPage) {
            $pages[] = [
                'type' => 'songs',
                'pageNumber' => count($pages) + 1,
                'columns' => $songPage['columns'],
            ];
        }

        $pdf = Pdf::loadView('pdf.setlist', [
            'setlist' => $setlist,
            'songsData' => $songsData,
            'pages' => $pages,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Repertorio_{$setlist->title}.pdf");
    }

    /**
     * Renderiza las líneas de la canción en HTML optimizado para PDF/DomPDF.
     */
    /**
     * @return list<array{label: string, lines: list<array{type: string, html?: string, content?: string}>}>
     */
    private function renderSongHtml(Song $song, int $semitones = 0, ?string $targetKey = null, bool $compact = false): array
    {
        $renderedSections = [];

        foreach ($song->sections as $section) {
            $linesHtml = [];
            foreach ($section->lines as $line) {
                if ($line->type === 'chord_lyrics') {
                    $linesHtml[] = [
                        'type' => 'chord_lyrics',
                        'content' => $line->content,
                        'html' => $this->formatChordProForPdf(
                            $line->content,
                            $semitones,
                            $targetKey,
                            $compact,
                            $section->type === 'chorus',
                        ),
                    ];
                } else {
                    $linesHtml[] = [
                        'type' => $line->type,
                        'content' => $line->content,
                    ];
                }
            }

            $renderedSections[] = [
                'type' => $section->type,
                'label' => $section->label ?? $section->type,
                'lines' => $linesHtml,
            ];
        }

        return $renderedSections;
    }

    private function formatChordProForPdf(
        string $content,
        int $semitones = 0,
        ?string $targetKey = null,
        bool $compact = false,
        bool $boldLyrics = false,
    ): string {
        $pattern = '/\[([^\]]+)\]([^\[]*)|([^\[]+)/';

        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            $escapedContent = htmlspecialchars($content);

            return $boldLyrics ? '<strong>' . $escapedContent . '</strong>' : $escapedContent;
        }

        $html = '<table style="border-collapse: collapse; margin-bottom: 4px; display: inline-table;"><tr>';

        foreach ($matches as $match) {
            $chord = ! empty($match[1]) ? $match[1] : '';
            $text = ! empty($match[1]) ? ($match[2] ?? '') : ($match[3] ?? '');

            if ($chord === '' && $text === '') {
                continue;
            }

            $displayChord = $chord;
            if ($chord !== '' && ($semitones !== 0 || $targetKey !== null)) {
                $displayChord = ChordTransposer::transposeChord($chord, $semitones, $targetKey);
            }

            $formattedText = $compact
                ? htmlspecialchars($text)
                : str_replace(' ', '&nbsp;', htmlspecialchars($text));
            if ($boldLyrics && $formattedText !== '') {
                $formattedText = '<strong>' . $formattedText . '</strong>';
            }

            $html .= '<td style="padding: 0; vertical-align: bottom; text-align: left;">';
            $html .= '<div style="font-weight: bold; color: #2563eb; font-size: ' . ($compact ? '8' : '11') . 'px; font-family: monospace; height: ' . ($compact ? '10' : '14') . 'px;">' . htmlspecialchars($displayChord) . '</div>';
            $html .= '<div style="font-size: ' . ($compact ? '9' : '13') . 'px; font-family: monospace; color: #111827; white-space: ' . ($compact ? 'pre-wrap' : 'normal') . ';">' . ($formattedText !== '' ? $formattedText : '&nbsp;') . '</div>';
            $html .= '</td>';
        }

        $html .= '</tr></table>';

        return $html;
    }

    private function inferSongKey(Song $song): string
    {
        $content = $song->sections
            ->flatMap(fn($section) => $section->lines
                ->where('type', 'chord_lyrics')
                ->pluck('content'))
            ->implode("\n");

        return ChordTransposer::inferKeyFromContent($content) ?? 'C';
    }
}
