<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use App\Services\ChordPro\ChordTransposer;

class SetlistMarkdownExporter
{
    public function export(Setlist $setlist): string
    {
        $setlist->loadMissing('songs.sections.lines');

        $content = ['# '.$setlist->title, ''];
        if ($setlist->scheduled_at) {
            $content[] = '**Fecha:** '.$setlist->scheduled_at->format('d/m/Y');
        }
        if ($setlist->description) {
            $content[] = $setlist->description;
        }
        $content[] = '';

        foreach ($setlist->songs as $songIndex => $song) {
            $pivot = $song->getRelation('pivot');
            $originalKey = $song->original_key ?? $this->inferSongKey($song);
            $customKey = data_get($pivot, 'custom_key');
            $targetKey = is_string($customKey) && $customKey !== '' ? $customKey : $originalKey;
            $semitones = ChordTransposer::semitonesBetweenKeys($originalKey, $targetKey);
            $notes = data_get($pivot, 'notes');

            if ($songIndex > 0) {
                $content[] = '---';
                $content[] = '';
            }

            $content[] = '## '.($songIndex + 1).'. '.$song->title;
            $content[] = '**Artista:** '.($song->artist ?: 'Desconocido').' | **Tono:** '.$targetKey;
            if ($song->capo) {
                $content[] = '**Capo:** '.$song->capo;
            }
            if (is_string($notes) && $notes !== '') {
                $content[] = '**Nota para la banda:** '.$notes;
            }
            $content[] = '';

            foreach ($song->sections as $section) {
                $content[] = '### '.$this->escapeMarkdown($section->label ?? $section->type);
                $content[] = '';

                foreach ($section->lines as $line) {
                    if ($line->type === 'chord_lyrics') {
                        $content[] = $this->formatChordLyrics($line->content, $semitones, $targetKey);
                    } elseif ($line->type === 'comment') {
                        $content[] = '> **Nota:** '.$this->escapeMarkdown($line->content);
                    } elseif ($line->type === 'tab_line') {
                        $content[] = '```text';
                        $content[] = htmlspecialchars($line->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $content[] = '```';
                    } else {
                        $content[] = $this->escapeMarkdown($line->content);
                    }
                }

                $content[] = '';
            }
        }

        return implode("\n", $content);
    }

    private function formatChordLyrics(string $content, int $semitones, string $targetKey): string
    {
        $pattern = '/\[([^\]]+)\]([^\[]*)|([^\[]+)/';
        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            return $this->escapeMarkdown($content);
        }

        $chordsAt = [];
        $lyrics = '';
        foreach ($matches as $match) {
            $chord = $match[1] ?? '';
            $text = $chord !== '' ? ($match[2] ?? '') : ($match[3] ?? '');

            if ($chord !== '') {
                $chord = ChordTransposer::transposeChord($chord, $semitones, $targetKey);
                $position = mb_strwidth($lyrics, 'UTF-8');
                $chordsAt[$position][] = $chord;
            }

            $lyrics .= $text;
        }

        if ($chordsAt === []) {
            return $this->escapeMarkdown($lyrics);
        }

        ksort($chordsAt);
        $chordLine = '';
        $chordLinePosition = 0;
        foreach ($chordsAt as $position => $chords) {
            $chordLine .= str_repeat(' ', max(0, $position - $chordLinePosition));

            foreach ($chords as $chordIndex => $chord) {
                if ($chordIndex > 0) {
                    $chordLine .= ' ';
                    $chordLinePosition++;
                }

                $escapedChord = htmlspecialchars($chord, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $chordLine .= '<strong>'.$escapedChord.'</strong>';
                $chordLinePosition += mb_strwidth($chord, 'UTF-8');
            }
        }

        return '<span style="font-family: monospace; white-space: pre;">'
            .$chordLine.'<br>'.$this->escapeMarkdown($lyrics)
            .'</span>';
    }

    private function escapeMarkdown(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function inferSongKey(Song $song): string
    {
        $content = $song->sections
            ->flatMap(fn ($section) => $section->lines
                ->where('type', 'chord_lyrics')
                ->pluck('content'))
            ->implode("\n");

        return ChordTransposer::inferKeyFromContent($content) ?? 'C';
    }
}
