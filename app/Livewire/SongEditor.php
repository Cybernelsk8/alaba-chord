<?php

namespace App\Livewire;

use App\Models\Song;
use App\Models\SongLine;
use App\Models\SongSection;
use App\Services\ChordPro\ChordTransposer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SongEditor extends Component
{
    public Song $song;

    public int $semitones = 0;

    public bool $isEditingRaw = false;

    public string $rawChordPro = '';

    // Modal de Edición de Acorde
    public bool $showChordModal = false;

    public ?int $editingSectionId = null;

    public ?int $editingLineId = null;

    public int $editingSegmentIndex = 0;

    public string $selectedChord = '';

    // Modales / Modos del Paso 3 (Edición de Secciones y Líneas)
    public bool $showSectionModal = false;

    public string $newSectionType = 'verse';

    public string $newSectionLabel = '';

    public bool $showLineEditModal = false;

    public ?int $editingLineTextId = null;

    public string $editingLineContent = '';

    public string $editingLineType = 'chord_lyrics';

    public function mount(Song $song)
    {
        $this->song = $song->load('sections.lines');
        $this->rawChordPro = $this->buildRawChordPro();
    }

    public function transpose(int $delta)
    {
        $this->semitones += $delta;
    }

    public function resetTranspose()
    {
        $this->semitones = 0;
    }

    private function buildRawChordPro(): string
    {
        $content = [];
        foreach ($this->song->sections as $section) {
            $content[] = '{'.$section->type.': '.($section->label ?? '').'}';
            foreach ($section->lines as $line) {
                $content[] = $line->content;
            }
        }

        return implode("\n", $content);
    }

    public function getTranspositionStateProperty()
    {
        $effectiveKey = $this->song->original_key
            ?? ChordTransposer::inferKeyFromContent($this->rawChordPro)
            ?? 'C';

        return ChordTransposer::transposeSong($effectiveKey, $this->semitones);
    }

    public function renderInteractiveLine(string $content, int $sectionIndex, int $lineIndex, int $sectionId, int $lineId): string
    {
        $pattern = '/\[([^\]]+)\]([^\[]*)|([^\[]+)/';

        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            return htmlspecialchars($content);
        }

        $html = '';

        foreach ($matches as $segmentIndex => $match) {
            $chord = '';
            $text = '';

            if (! empty($match[1])) {
                $chord = $match[1];
                $text = $match[2] ?? '';
            } else {
                $text = $match[3] ?? '';
            }

            if ($chord === '' && $text === '') {
                continue;
            }

            $displayChord = $chord;
            if ($chord !== '' && $this->semitones !== 0) {
                $displayChord = ChordTransposer::transposeChord($chord, $this->semitones);
            }

            $endsWithSpace = preg_match('/\s$/u', $text);
            $formattedText = str_replace(' ', '&nbsp;', htmlspecialchars($text));
            $escapedChord = htmlspecialchars($chord, ENT_QUOTES);

            $spacingClass = $endsWithSpace ? 'mr-1.5 px-0.5' : '-mr-[1px] px-0';

            $html .= sprintf(
                '<span wire:click="openChordModal(%d, %d, %d, \'%s\')" class="inline-flex flex-col items-start relative group cursor-pointer %s hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded transition-colors">',
                $sectionId,
                $lineId,
                $segmentIndex,
                $escapedChord,
                $spacingClass
            );

            if ($displayChord !== '') {
                $html .= sprintf(
                    '<span class="font-bold text-accent-600 dark:text-accent-400 text-sm h-5 leading-none select-none group-hover:scale-110 transition-transform">%s</span>',
                    htmlspecialchars($displayChord)
                );
            } else {
                $html .= '<span class="h-5 leading-none select-none opacity-0 group-hover:opacity-40 text-xs text-zinc-400">+</span>';
            }

            $html .= sprintf(
                '<span class="text-zinc-800 dark:text-zinc-200 select-text">%s</span>',
                $formattedText !== '' ? $formattedText : '&nbsp;'
            );

            $html .= '</span>';
        }

        return $html;
    }

    // ==========================================
    // PASO 3: GESTIÓN DE SECCIONES (SECTIONS)
    // ==========================================

    public function openAddSectionModal(): void
    {
        $this->newSectionType = 'verse';
        $this->newSectionLabel = 'Verso';
        $this->showSectionModal = true;
    }

    public function addSection(): void
    {
        $maxPosition = $this->song->sections()->max('position') ?? 0;

        $section = SongSection::create([
            'song_id' => $this->song->id,
            'type' => $this->newSectionType,
            'label' => ! empty($this->newSectionLabel) ? $this->newSectionLabel : ucfirst($this->newSectionType),
            'position' => $maxPosition + 1,
        ]);

        // Agregamos una primera línea por defecto para la nueva sección
        $section->lines()->create([
            'position' => 1,
            'type' => 'chord_lyrics',
            'content' => 'Nueva línea...',
        ]);

        $this->refreshSong();
        $this->showSectionModal = false;
    }

    public function deleteSection(int $sectionId): void
    {
        SongSection::where('id', $sectionId)->where('song_id', $this->song->id)->delete();
        $this->reorderSectionPositions();
        $this->refreshSong();
    }

    public function moveSection(int $sectionId, string $direction): void
    {
        $sections = $this->song->sections()->orderBy('position')->get();
        $currentIndex = $sections->search(fn ($s) => $s->id === $sectionId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex >= 0 && $targetIndex < $sections->count()) {
            $currentSection = $sections[$currentIndex];
            $targetSection = $sections[$targetIndex];

            // Intercambiar posiciones
            $tempPos = $currentSection->position;
            $currentSection->update(['position' => $targetSection->position]);
            $targetSection->update(['position' => $tempPos]);

            $this->refreshSong();
        }
    }

    private function reorderSectionPositions(): void
    {
        $sections = $this->song->sections()->orderBy('position')->get();
        foreach ($sections as $index => $sec) {
            $sec->update(['position' => $index + 1]);
        }
    }

    // ==========================================
    // PASO 3: GESTIÓN DE LÍNEAS (LINES)
    // ==========================================

    public function addLine(int $sectionId, string $type = 'chord_lyrics'): void
    {
        $section = SongSection::find($sectionId);
        if (! $section) {
            return;
        }

        $maxPosition = $section->lines()->max('position') ?? 0;

        $section->lines()->create([
            'position' => $maxPosition + 1,
            'type' => $type,
            'content' => $type === 'comment' ? 'Comentario...' : 'Nueva línea...',
        ]);

        $this->refreshSong();
    }

    public function openEditLineModal(int $lineId): void
    {
        $line = SongLine::find($lineId);
        if (! $line) {
            return;
        }

        $this->editingLineTextId = $line->id;
        $this->editingLineContent = $line->content;
        $this->editingLineType = $line->type;
        $this->showLineEditModal = true;
    }

    public function saveLineContent(): void
    {
        if (! $this->editingLineTextId) {
            return;
        }

        $line = SongLine::find($this->editingLineTextId);
        if ($line) {
            $line->update([
                'content' => $this->editingLineContent,
                'type' => $this->editingLineType,
            ]);
        }

        $this->refreshSong();
        $this->showLineEditModal = false;
    }

    public function deleteLine(int $lineId): void
    {
        $line = SongLine::find($lineId);
        if (! $line) {
            return;
        }

        $sectionId = $line->song_section_id;
        $line->delete();

        // Reordenar posiciones en la sección
        $lines = SongLine::where('song_section_id', $sectionId)->orderBy('position')->get();
        foreach ($lines as $index => $l) {
            $l->update(['position' => $index + 1]);
        }

        $this->refreshSong();
    }

    public function moveLine(int $lineId, string $direction): void
    {
        $line = SongLine::find($lineId);
        if (! $line) {
            return;
        }

        $lines = SongLine::where('song_section_id', $line->song_section_id)->orderBy('position')->get();
        $currentIndex = $lines->search(fn ($l) => $l->id === $lineId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex >= 0 && $targetIndex < $lines->count()) {
            $currentLine = $lines[$currentIndex];
            $targetLine = $lines[$targetIndex];

            $tempPos = $currentLine->position;
            $currentLine->update(['position' => $targetLine->position]);
            $targetLine->update(['position' => $tempPos]);

            $this->refreshSong();
        }
    }

    // Método auxiliar para recargar la relación y regenerar el ChordPro raw
    private function refreshSong(): void
    {
        $this->song->load('sections.lines');
        $this->rawChordPro = $this->buildRawChordPro();
    }

    // ==========================================
    // EDICIÓN RÁPIDA DE ACORDES (DEL PASO 2)
    // ==========================================

    public function openChordModal(int $sectionId, int $lineId, int $segmentIndex, string $currentChord = ''): void
    {
        $this->editingSectionId = $sectionId;
        $this->editingLineId = $lineId;
        $this->editingSegmentIndex = $segmentIndex;
        $this->selectedChord = $currentChord;
        $this->showChordModal = true;
    }

    public function updateChord(): void
    {
        $line = SongLine::find($this->editingLineId);

        if (! $line) {
            $this->showChordModal = false;

            return;
        }

        $pattern = '/\[([^\]]+)\]([^\[]*)|([^\[]+)/';
        if (! preg_match_all($pattern, $line->content, $matches, PREG_SET_ORDER)) {
            $this->showChordModal = false;

            return;
        }

        $newContent = '';

        foreach ($matches as $index => $match) {
            $chord = ! empty($match[1]) ? $match[1] : '';
            $text = ! empty($match[1]) ? ($match[2] ?? '') : ($match[3] ?? '');

            if ($index === $this->editingSegmentIndex) {
                $chord = trim($this->selectedChord);
            }

            if (! empty($chord)) {
                $newContent .= "[{$chord}]{$text}";
            } else {
                $newContent .= $text;
            }
        }

        $line->update(['content' => $newContent]);
        $this->refreshSong();
        $this->showChordModal = false;
    }

    public function removeChord(): void
    {
        $this->selectedChord = '';
        $this->updateChord();
    }

    public function render()
    {
        return view('livewire.song-editor', [
            'transposition' => $this->transpositionState,
        ]);
    }

    public function save()
    {
        $this->validate([
            'rawChordPro' => 'required|string',
        ]);

        if (! $this->song->exists) {
            $this->song->user_id = $this->song->user_id ?? auth()->id();
            $this->song->title = $this->song->title ?? 'Sin título';
            $this->song->save();
        }

        $songId = $this->song->id;

        DB::transaction(function () use ($songId) {
            SongSection::where('song_id', $songId)->delete();

            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $this->rawChordPro));

            $songData = [
                'title' => $this->song->title,
                'meta' => [],
            ];

            $sections = [];
            $currentSection = [
                'type' => 'verse',
                'label' => 'Verso',
                'lines' => [],
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                if (preg_match('/^\{([^:]+)(?::\s*(.*))?\}$/', $line, $matches)) {
                    $directive = strtolower(trim($matches[1]));
                    $value = isset($matches[2]) ? trim($matches[2]) : null;

                    if (in_array($directive, ['title', 't']) && $value) {
                        $songData['title'] = $value;
                    }
                    if (in_array($directive, ['artist']) && $value) {
                        $songData['artist'] = $value;
                    }
                    if (in_array($directive, ['key']) && $value) {
                        $songData['original_key'] = $value;
                    }
                    if (in_array($directive, ['capo']) && $value) {
                        $songData['capo'] = (int) $value;
                    }
                    if (in_array($directive, ['tempo']) && $value) {
                        $songData['tempo'] = (int) $value;
                    }

                    if (in_array($directive, ['start_of_chorus', 'soc'])) {
                        if (! empty($currentSection['lines'])) {
                            $sections[] = $currentSection;
                        }
                        $currentSection = ['type' => 'chorus', 'label' => $value ?? 'Coro', 'lines' => []];
                    } elseif (in_array($directive, ['start_of_verse', 'sov'])) {
                        if (! empty($currentSection['lines'])) {
                            $sections[] = $currentSection;
                        }
                        $currentSection = ['type' => 'verse', 'label' => $value ?? 'Verso', 'lines' => []];
                    } elseif (in_array($directive, ['start_of_bridge', 'sob'])) {
                        if (! empty($currentSection['lines'])) {
                            $sections[] = $currentSection;
                        }
                        $currentSection = ['type' => 'bridge', 'label' => $value ?? 'Puente', 'lines' => []];
                    }

                    continue;
                }

                $currentSection['lines'][] = [
                    'type' => 'chord_lyrics',
                    'content' => $line,
                ];
            }

            if (! empty($currentSection['lines'])) {
                $sections[] = $currentSection;
            }

            $this->song->update([
                'title' => $songData['title'] ?? $this->song->title,
                'artist' => $songData['artist'] ?? $this->song->artist,
                'original_key' => $songData['original_key'] ?? $this->song->original_key,
                'capo' => $songData['capo'] ?? $this->song->capo,
                'tempo' => $songData['tempo'] ?? $this->song->tempo,
            ]);

            foreach ($sections as $secIndex => $secData) {
                $section = SongSection::create([
                    'song_id' => $songId,
                    'type' => $secData['type'],
                    'label' => $secData['label'],
                    'position' => $secIndex + 1,
                ]);

                foreach ($secData['lines'] as $lineIndex => $lineData) {
                    $section->lines()->create([
                        'position' => $lineIndex + 1,
                        'type' => $lineData['type'],
                        'content' => $lineData['content'],
                    ]);
                }
            }
        });

        $this->refreshSong();
        $this->isEditingRaw = false;
    }
}
