<?php

namespace App\Livewire;

use App\Models\Song;
use App\Models\SongLine;
use App\Models\SongSection;
use App\Services\ChordDiagramService;
use App\Services\ChordPro\ChordCatalog;
use App\Services\ChordPro\ChordProExporter;
use App\Services\ChordPro\ChordProImporter;
use App\Services\ChordPro\ChordTransposer;
use App\Services\ChordPro\DirectiveMap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/** @property-read array{newKey: string, useFlats: bool} $transpositionState */
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

    public string $selectedChordRoot = 'C';

    public string $selectedChordQuality = '';

    public string $selectedChordBass = '';

    // Modales / Modos del Paso 3 (Edición de Secciones y Líneas)
    public bool $showSectionModal = false;

    public string $newSectionType = 'verse';

    public string $newSectionLabel = '';

    public bool $showLineEditModal = false;

    public ?int $editingLineTextId = null;

    public string $editingLineContent = '';

    public string $editingLineType = 'chord_lyrics';

    public function mount(?Song $song = null): void
    {
        $this->song = ($song ?? new Song)->load('sections.lines');
        $this->rawChordPro = $this->buildRawChordPro();
    }

    public function transpose(int $delta): void
    {
        $this->semitones += $delta;
    }

    public function resetTranspose(): void
    {
        $this->semitones = 0;
    }

    /**
     * Aplica la transposición actual de forma permanente a todas las líneas
     * y guarda los cambios en la base de datos.
     */
    public function saveTransposition(): void
    {
        if ($this->semitones === 0) {
            return;
        }

        DB::transaction(function () {
            // 1. Transponer y guardar cada línea de la canción
            foreach ($this->song->sections as $section) {
                foreach ($section->lines as $line) {
                    if (! empty($line->content) && $line->type === 'chord_lyrics') {
                        $line->update([
                            'content' => $this->transposeLineContent($line->content, $this->semitones),
                        ]);
                    }
                }
            }

            // 2. Actualizar el tono de la canción
            $newKey = $this->transpositionState['newKey'] ?? $this->song->original_key;
            $this->song->update([
                'original_key' => $newKey,
            ]);
        });

        // 3. Resetear semitonos a 0 y recargar la estructura fresca
        $this->semitones = 0;
        $this->refreshSong();

        $this->dispatch('notify', message: 'Tono guardado correctamente de forma permanente.');
    }

    /**
     * Reemplaza todos los acordes en corchetes [Acorde] transponiéndolos según los semitonos indicados.
     */
    private function transposeLineContent(string $content, int $semitones): string
    {
        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($semitones) {
            $chord = $matches[1];
            $transposed = ChordTransposer::transposeChord($chord, $semitones);

            return "[{$transposed}]";
        }, $content);
    }

    private function buildRawChordPro(): string
    {
        return ChordProExporter::export($this->song);
    }

    /** @return array{newKey: string, useFlats: bool} */
    public function getTranspositionStateProperty(): array
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

            // Generar el diagrama SVG si existe un acorde a mostrar
            $svgDiagram = '';
            if ($displayChord !== '') {
                $svgDiagram = ChordDiagramService::getSvg($displayChord);
            }

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

                // Tooltip emergente con el diagrama SVG al pasar el ratón (hover)
                if (! empty($svgDiagram)) {
                    $html .= sprintf(
                        '<span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:flex flex-col items-center z-50 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-xl rounded-lg p-2 transition-all">'
                            . '<span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 mb-1">%s</span>'
                            . '%s'
                            . '</span>',
                        htmlspecialchars($displayChord),
                        $svgDiagram
                    );
                }
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
        $this->validate([
            'newSectionType' => ['required', 'string', Rule::in(array_column(DirectiveMap::sectionDefinitions(), 'type'))],
            'newSectionLabel' => ['nullable', 'string', 'max:255'],
        ]);

        $maxPosition = $this->song->sections()->max('position') ?? 0;

        $section = SongSection::create([
            'song_id' => $this->song->id,
            'type' => $this->newSectionType,
            'label' => ! empty($this->newSectionLabel) ? $this->newSectionLabel : ucfirst($this->newSectionType),
            'position' => $maxPosition + 1,
        ]);

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
        $currentIndex = $sections->search(fn($s) => $s->id === $sectionId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex >= 0 && $targetIndex < $sections->count()) {
            $currentSection = $sections[$currentIndex];
            $targetSection = $sections[$targetIndex];

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
        if (! in_array($type, ['chord_lyrics', 'comment', 'tab_line'], true)) {
            return;
        }

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

        $this->validate([
            'editingLineContent' => ['present', 'string'],
            'editingLineType' => ['required', 'string', Rule::in(['chord_lyrics', 'comment', 'tab_line'])],
        ]);

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
        $currentIndex = $lines->search(fn($l) => $l->id === $lineId);

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

    private function refreshSong(): void
    {
        $this->song->load('sections.lines');
        $this->rawChordPro = $this->buildRawChordPro();
    }

    // ==========================================
    // EDICIÓN RÁPIDA DE ACORDES
    // ==========================================

    public function openChordModal(int $sectionId, int $lineId, int $segmentIndex, string $currentChord = ''): void
    {
        $this->editingSectionId = $sectionId;
        $this->editingLineId = $lineId;
        $this->editingSegmentIndex = $segmentIndex;
        $this->selectedChord = $currentChord;
        $parts = ChordCatalog::parseChord($currentChord);
        $this->selectedChordRoot = $parts['root'] ?? 'C';
        $this->selectedChordQuality = in_array($parts['quality'] ?? '', ChordCatalog::qualities(), true)
            ? ($parts['quality'] ?? '')
            : '';
        $this->selectedChordBass = $parts['bass'] ?? '';
        $this->showChordModal = true;
    }

    public function updateSelectedChordFromCatalog(): void
    {
        if (
            ! in_array($this->selectedChordRoot, ChordCatalog::roots(), true)
            || ! in_array($this->selectedChordQuality, ChordCatalog::qualities(), true)
            || ($this->selectedChordBass !== '' && ! in_array($this->selectedChordBass, ChordCatalog::roots(), true))
        ) {
            return;
        }

        $this->selectedChord = $this->selectedChordRoot
            . $this->selectedChordQuality
            . ($this->selectedChordBass !== '' ? '/' . $this->selectedChordBass : '');
    }

    public function insertChordProDirective(string $directive): void
    {
        $snippet = null;
        $metadata = DirectiveMap::metadata($directive);
        $section = DirectiveMap::sectionStart($directive);

        if ($metadata !== null) {
            $snippet = '{' . $metadata['name'] . ': }';
        } elseif ($section !== null) {
            $snippet = '{' . DirectiveMap::startDirective($section['type']) . ': ' . $section['label'] . '}';
        } elseif (($sectionType = DirectiveMap::sectionEnd($directive)) !== null) {
            $snippet = '{' . DirectiveMap::endDirective($sectionType) . '}';
        } elseif (in_array($directive, DirectiveMap::commentAliases(), true)) {
            $snippet = '{comment: }';
        }

        if ($snippet === null) {
            return;
        }

        $prefix = $this->rawChordPro === '' || str_ends_with($this->rawChordPro, "\n") ? '' : "\n";
        $this->rawChordPro .= $prefix . $snippet . "\n";
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

    public function render(): View
    {
        return view('livewire.song-editor', [
            'transposition' => $this->transpositionState,
            'metadataDirectives' => DirectiveMap::metadataDefinitions(),
            'sectionDirectives' => DirectiveMap::sectionDefinitions(),
            'chordRoots' => ChordCatalog::roots(),
            'chordQualities' => ChordCatalog::qualities(),
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'rawChordPro' => 'required|string',
        ]);

        $parsed = ChordProImporter::parse($this->rawChordPro, [
            'title' => $this->song->title,
            'subtitle' => $this->song->subtitle,
            'artist' => $this->song->artist,
            'original_key' => $this->song->original_key,
            'capo' => $this->song->capo,
            'tempo' => $this->song->tempo,
            'time_signature' => $this->song->time_signature,
            'duration' => $this->song->duration,
            'meta' => $this->song->meta ?? [],
            'user_id' => $this->song->user_id,
        ]);

        if (! $this->song->exists) {
            $userId = Auth::id();
            if ($userId !== null && $this->song->getAttribute('user_id') === null) {
                $this->song->setAttribute('user_id', $userId);
            }
            $this->song->title = $this->song->title ?? 'Sin título';
            $this->song->save();
        }

        $songId = $this->song->id;

        DB::transaction(function () use ($songId, $parsed) {
            SongSection::where('song_id', $songId)->delete();
            $fields = [
                'title',
                'subtitle',
                'artist',
                'original_key',
                'capo',
                'tempo',
                'time_signature',
                'duration',
                'meta',
            ];
            $songData = array_intersect_key($parsed['song'], array_flip($fields));
            $this->song->update($songData);

            ChordProImporter::persistSections($this->song, $parsed['sections']);
        });

        $this->refreshSong();
        $this->isEditingRaw = false;
    }
}
