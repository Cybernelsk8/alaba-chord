<?php

namespace App\Livewire;

use App\Models\Song;
use Livewire\Component;

class SongPresenter extends Component
{
    public Song $song;

    public int $currentSectionIndex = 0;

    public bool $showChords = true;

    public int $fontSize = 32; // Tamaño de letra base en px

    public function mount(Song $song)
    {
        $this->song = $song->load('sections.lines');
    }

    public function nextSection(): void
    {
        if ($this->currentSectionIndex < $this->song->sections->count() - 1) {
            $this->currentSectionIndex++;
        }
    }

    public function previousSection(): void
    {
        if ($this->currentSectionIndex > 0) {
            $this->currentSectionIndex--;
        }
    }

    public function toggleChords(): void
    {
        $this->showChords = ! $this->showChords;
    }

    public function adjustFontSize(int $delta): void
    {
        $this->fontSize = max(18, min(64, $this->fontSize + $delta));
    }

    public function renderLyricsOnly(string $content): string
    {
        // Remueve los acordes entre corchetes [Acorde] dejando solo la letra
        $cleanText = preg_replace('/\[[^\]]+\]/', '', $content);

        return htmlspecialchars($cleanText);
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

            $endsWithSpace = preg_match('/\s$/u', $text);
            $formattedText = str_replace(' ', '&nbsp;', htmlspecialchars($text));

            $spacingClass = $endsWithSpace ? 'mr-3 px-0.5' : '-mr-[1px] px-0';

            $html .= sprintf(
                '<span class="inline-flex flex-col items-center relative %s">',
                $spacingClass
            );

            if ($chord !== '') {
                $html .= sprintf(
                    '<span class="font-bold text-accent-400 text-[0.75em] leading-none select-none mb-1">%s</span>',
                    htmlspecialchars($chord)
                );
            } else {
                $html .= '<span class="text-[0.75em] leading-none select-none opacity-0">&nbsp;</span>';
            }

            $html .= sprintf(
                '<span class="text-white select-text">%s</span>',
                $formattedText !== '' ? $formattedText : '&nbsp;'
            );

            $html .= '</span>';
        }

        return $html;
    }

    public function render()
    {
        return view('livewire.song-presenter', [
            'currentSection' => $this->song->sections[$this->currentSectionIndex] ?? null,
        ])->layout('layouts.guest'); // O un layout limpio sin navegación
    }
}
