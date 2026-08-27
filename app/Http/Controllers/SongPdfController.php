<?php

namespace App\Http\Controllers;

use App\Models\Setlist;
use App\Models\Song;
use App\Services\ChordPro\ChordTransposer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SongPdfController extends Controller
{
    /**
     * Exporta una canción individual a PDF con transposición opcional.
     */
    public function exportSong(Request $request, Song $song)
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
    public function exportSetlist(Setlist $setlist)
    {
        $setlist->load('songs.sections.lines');

        $songsData = [];
        foreach ($setlist->songs as $song) {
            $songsData[] = [
                'song' => $song,
                'htmlContent' => $this->renderSongHtml($song, 0, $song->pivot->custom_key),
                'customKey' => $song->pivot->custom_key,
                'notes' => $song->pivot->notes,
            ];
        }

        $pdf = Pdf::loadView('pdf.setlist', [
            'setlist' => $setlist,
            'songsData' => $songsData,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Repertorio_{$setlist->title}.pdf");
    }

    /**
     * Renderiza las líneas de la canción en HTML optimizado para PDF/DomPDF.
     */
    private function renderSongHtml(Song $song, int $semitones = 0, ?string $targetKey = null): array
    {
        $renderedSections = [];

        foreach ($song->sections as $section) {
            $linesHtml = [];
            foreach ($section->lines as $line) {
                if ($line->type === 'chord_lyrics') {
                    $linesHtml[] = [
                        'type' => 'chord_lyrics',
                        'html' => $this->formatChordProForPdf($line->content, $semitones),
                    ];
                } else {
                    $linesHtml[] = [
                        'type' => $line->type,
                        'content' => $line->content,
                    ];
                }
            }

            $renderedSections[] = [
                'label' => $section->label ?? $section->type,
                'lines' => $linesHtml,
            ];
        }

        return $renderedSections;
    }

    private function formatChordProForPdf(string $content, int $semitones = 0): string
    {
        $pattern = '/\[([^\]]+)\]([^\[]*)|([^\[]+)/';

        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            return htmlspecialchars($content);
        }

        $html = '<table style="border-collapse: collapse; margin-bottom: 4px; display: inline-table;"><tr>';

        foreach ($matches as $match) {
            $chord = ! empty($match[1]) ? $match[1] : '';
            $text = ! empty($match[1]) ? ($match[2] ?? '') : ($match[3] ?? '');

            if ($chord === '' && $text === '') {
                continue;
            }

            $displayChord = $chord;
            if ($chord !== '' && $semitones !== 0) {
                $displayChord = ChordTransposer::transposeChord($chord, $semitones);
            }

            $formattedText = str_replace(' ', '&nbsp;', htmlspecialchars($text));

            $html .= '<td style="padding: 0; vertical-align: bottom; text-align: left;">';
            $html .= '<div style="font-weight: bold; color: #2563eb; font-size: 11px; font-family: monospace; height: 14px;">'.htmlspecialchars($displayChord).'</div>';
            $html .= '<div style="font-size: 13px; font-family: monospace; color: #111827;">'.($formattedText !== '' ? $formattedText : '&nbsp;').'</div>';
            $html .= '</td>';
        }

        $html .= '</tr></table>';

        return $html;
    }
}
