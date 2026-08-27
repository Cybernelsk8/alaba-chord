<?php

namespace App\Livewire;

use App\Models\Song;
use App\Services\ChordDiagramService;
use Livewire\Component;

class SongLearner extends Component
{
    public Song $song;

    public function mount(Song $song)
    {
        $this->song = $song->load('sections.lines');
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
                // Generamos el SVG del acorde
                $diagramSvg = ChordDiagramService::getSvg($chord);

                // Envolvemos el acorde en un Popover de Flux UI o HTML tooltip con el SVG
                $html .= sprintf(
                    '<div class="relative group cursor-pointer">
                        <span class="font-bold text-accent-600 dark:text-accent-400 text-base h-6 leading-none select-none border-b border-dashed border-accent-400/50 hover:border-accent-500">%s</span>
                        
                        <!-- POPOVER / TOOLTIP CON EL SVG -->
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:flex flex-col items-center p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 pointer-events-none">
                            <span class="text-xs font-bold text-zinc-900 dark:text-white mb-1">%s (Guitarra)</span>
                            %s
                        </div>
                    </div>',
                    htmlspecialchars($chord),
                    htmlspecialchars($chord),
                    $diagramSvg
                );
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

    public function render()
    {
        return view('livewire.song-learner');
    }
}
