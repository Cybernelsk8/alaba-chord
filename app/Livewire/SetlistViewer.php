<?php

namespace App\Livewire;

use App\Models\Setlist;
use App\Models\Song;
use App\Services\ChordPro\ChordTransposer;
use Livewire\Component;

class SetlistViewer extends Component
{
    public Setlist $setlist;

    public int $currentIndex = 0;

    public int $semitones = 0;

    public function mount(Setlist $setlist)
    {
        $this->setlist = $setlist->load('songs.sections.lines');
        $this->updateSemitonesForCurrentSong();
    }

    public function nextSong(): void
    {
        if ($this->currentIndex < $this->setlist->songs->count() - 1) {
            $this->currentIndex++;
            $this->updateSemitonesForCurrentSong();
        }
    }

    public function previousSong(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->updateSemitonesForCurrentSong();
        }
    }

    public function selectSongIndex(int $index): void
    {
        if ($index >= 0 && $index < $this->setlist->songs->count()) {
            $this->currentIndex = $index;
            $this->updateSemitonesForCurrentSong();
        }
    }

    public function transpose(int $delta): void
    {
        $this->semitones += $delta;
    }

    private function updateSemitonesForCurrentSong(): void
    {
        $currentSong = $this->currentSong;

        if (! $currentSong) {
            $this->semitones = 0;

            return;
        }

        // Si la canción tiene un tono personalizado en el setlist, calculamos la diferencia respecto al tono original
        $originalKey = $currentSong->original_key ?? 'C';
        $customKey = $currentSong->pivot->custom_key ?? $originalKey;

        // Si son distintos, podríamos calcular los semitonos iniciales (opcional), de momento inicializamos en 0
        $this->semitones = 0;
    }

    public function getCurrentSongProperty(): ?Song
    {
        return $this->setlist->songs[$this->currentIndex] ?? null;
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

            $displayChord = $chord;
            if ($chord !== '' && $this->semitones !== 0) {
                $displayChord = ChordTransposer::transposeChord($chord, $this->semitones);
            }

            $endsWithSpace = preg_match('/\s$/u', $text);
            $formattedText = str_replace(' ', '&nbsp;', htmlspecialchars($text));

            $spacingClass = $endsWithSpace ? 'mr-1.5 px-0.5' : '-mr-[1px] px-0';

            $html .= sprintf(
                '<span class="inline-flex flex-col items-start relative %s">',
                $spacingClass
            );

            if ($displayChord !== '') {
                $html .= sprintf(
                    '<span class="font-bold text-accent-600 dark:text-accent-400 text-base h-6 leading-none select-none">%s</span>',
                    htmlspecialchars($displayChord)
                );
            } else {
                $html .= '<span class="h-6 leading-none select-none opacity-0 text-xs">&nbsp;</span>';
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
        return view('livewire.setlist-viewer', [
            'currentSong' => $this->currentSong,
        ]);
    }
}
