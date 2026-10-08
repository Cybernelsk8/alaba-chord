<?php

namespace App\Livewire;

use App\Models\Song;
use App\Services\ChordDiagramService;
use App\Services\ChordPro\ChordTransposer;
use Illuminate\View\View;
use Livewire\Component;

class SongLearner extends Component
{
    public Song $song;

    public int $semitones = 0;

    public function mount(Song $song): void
    {
        $this->song = $song->load('sections.lines');
    }

    public function transpose(int $step): void
    {
        $this->semitones += $step;
    }

    public function resetTranspose(): void
    {
        $this->semitones = 0;
    }

    public function transposeChord(string $chord): string
    {
        $originalKey = $this->song->original_key ?? 'C';
        $targetKey = ChordTransposer::transposeKey($originalKey, $this->semitones);

        return ChordTransposer::transposeChord($chord, $this->semitones, $targetKey);
    }

    public function getTransposedKeyProperty(): string
    {
        $originalKey = $this->song->original_key ?? 'C';

        return ChordTransposer::transposeKey($originalKey, $this->semitones);
    }

    public function renderInteractiveLine(string $content): string
    {
        $pattern = '/\[([^\]]+)\]([^\[]*)|([^\[]+)/';

        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            return htmlspecialchars($content);
        }

        $html = '';

        foreach ($matches as $match) {
            $chord = ! empty($match[1]) ? $match[1] : '';
            $text = ! empty($match[1]) ? ($match[2] ?? '') : ($match[3] ?? '');

            if ($chord === '' && $text === '') {
                continue;
            }

            $formattedText = str_replace(' ', '&nbsp;', htmlspecialchars($text));

            $html .= '<span class="inline-flex flex-col items-start relative mr-2">';

            if ($chord !== '') {
                // Transponemos el acorde antes de renderizar
                $transposedChord = $this->transposeChord($chord);

                // Generamos el SVG del acorde con el tono transpuesto
                $diagramSvg = ChordDiagramService::getSvg($transposedChord);

                // Envolvemos el acorde en un Popover / Tooltip
                $html .= '<div class="relative group cursor-pointer">';
                $html .= sprintf(
                    '<span class="font-bold text-accent-600 dark:text-accent-400 text-base h-6 leading-none select-none border-b border-dashed border-accent-400/50 hover:border-accent-500">%s</span>',
                    htmlspecialchars($transposedChord)
                );
                if ($diagramSvg !== '') {
                    $html .= sprintf(
                        '<div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:flex flex-col items-center p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 pointer-events-none"><span class="text-xs font-bold text-zinc-900 dark:text-white mb-1">%s (Guitarra)</span>%s</div>',
                        htmlspecialchars($transposedChord),
                        $diagramSvg
                    );
                }
                $html .= '</div>';
            } else {
                $html .= '<span class="h-6 leading-none opacity-0">&nbsp;</span>';
            }

            $html .= sprintf(
                '<span class="text-zinc-800 dark:text-zinc-200 select-text">%s</span>',
                $formattedText !== '' ? $formattedText : '&nbsp;'
            );

            $html .= '</span>';
        }

        return $html;
    }

    public function render(): View
    {
        return view('livewire.song-learner');
    }
}
