<?php

namespace App\Livewire;

use App\Models\Setlist;
use App\Models\Song;
use App\Services\ChordPro\ChordCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class SetlistManager extends Component
{
    // Selección y Modales
    public ?Setlist $selectedSetlist = null;

    public bool $showCreateModal = false;

    public bool $showSongPickerModal = false;

    // Campos para Formulario de Setlist
    public string $title = '';

    public string $description = '';

    public ?string $scheduled_at = null;

    // Búsqueda de canciones
    public string $searchSong = '';

    // Edición rápida de notas/tono en pivote
    public ?int $editingPivotId = null;

    public string $customKey = '';

    public string $notes = '';

    public bool $showPivotModal = false;

    /** @var array<string, string> */
    protected array $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'scheduled_at' => 'nullable|date',
    ];

    public function mount(?int $setlistId = null): void
    {
        if ($setlistId) {
            $this->selectSetlist($setlistId);
        }
    }

    public function selectSetlist(int $setlistId): void
    {
        $this->selectedSetlist = Setlist::with('songs')->find($setlistId);
    }

    public function openCreateModal(): void
    {
        $this->reset(['title', 'description', 'scheduled_at']);
        $this->showCreateModal = true;
    }

    public function createSetlist(): void
    {
        $this->validate();

        $setlist = Setlist::create([
            'user_id' => Auth::id() ?? 1,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at,
        ]);

        $this->showCreateModal = false;
        $this->selectSetlist($setlist->id);
    }

    public function deleteSetlist(int $setlistId): void
    {
        Setlist::destroy($setlistId);

        if ($this->selectedSetlist?->id === $setlistId) {
            $this->selectedSetlist = null;
        }
    }

    // ==========================================
    // GESTIÓN DE CANCIONES DENTRO DEL REPERTORIO
    // ==========================================

    public function addSongToSetlist(int $songId): void
    {
        if (! $this->selectedSetlist) {
            return;
        }

        if ($this->selectedSetlist->songs()->whereKey($songId)->exists()) {
            return;
        }

        $song = Song::find($songId);
        if (! $song) {
            return;
        }

        DB::transaction(function () use ($songId, $song): void {
            $maxPosition = DB::table('setlist_song')
                ->where('setlist_id', $this->selectedSetlist->id)
                ->max('position') ?? 0;

            $this->selectedSetlist->songs()->attach($songId, [
                'position' => $maxPosition + 1,
                'custom_key' => $song->original_key,
                'notes' => '',
            ]);
        });

        $this->selectedSetlist->load('songs');
    }

    public function removeSongFromSetlist(int $songId): void
    {
        if (! $this->selectedSetlist) {
            return;
        }

        DB::transaction(function () use ($songId): void {
            $this->selectedSetlist->songs()->detach($songId);
            $this->reorderPositions();
        });
        $this->selectedSetlist->load('songs');
    }

    public function moveSong(int $songId, string $direction): void
    {
        if (! $this->selectedSetlist) {
            return;
        }

        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        DB::transaction(function () use ($songId, $direction): void {
            $pivots = DB::table('setlist_song')
                ->where('setlist_id', $this->selectedSetlist->id)
                ->orderBy('position')
                ->get()
                ->map(fn(object $pivot): array => [
                    'id' => (int) $pivot->id,
                    'song_id' => (int) $pivot->song_id,
                    'position' => (int) $pivot->position,
                ]);
            $currentIndex = $pivots->search(fn(array $pivot) => $pivot['song_id'] === $songId);

            if ($currentIndex === false) {
                return;
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
            if ($targetIndex < 0 || $targetIndex >= $pivots->count()) {
                return;
            }

            $currentPivot = $pivots[$currentIndex];
            $targetPivot = $pivots[$targetIndex];
            $tempPosition = $currentPivot['position'];

            DB::table('setlist_song')->where('id', $currentPivot['id'])
                ->update(['position' => $targetPivot['position']]);
            DB::table('setlist_song')->where('id', $targetPivot['id'])
                ->update(['position' => $tempPosition]);
        });

        $this->selectedSetlist->load('songs');
    }

    public function openPivotModal(int $pivotId, ?string $currentKey, ?string $currentNotes): void
    {
        $this->editingPivotId = $pivotId;
        $this->customKey = $currentKey;
        $this->notes = $currentNotes ?? '';
        $this->showPivotModal = true;
    }

    public function updatePivotDetails(): void
    {
        if (! $this->editingPivotId || ! $this->selectedSetlist) {
            return;
        }

        $validated = $this->validate([
            'customKey' => ['nullable', 'string', Rule::in(array_merge([''], ChordCatalog::keys()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::table('setlist_song')
            ->where('setlist_id', $this->selectedSetlist->id)
            ->where('id', $this->editingPivotId)
            ->update([
                'custom_key' => $validated['customKey'] ?: null,
                'notes' => $validated['notes'] ?: null,
            ]);

        $this->showPivotModal = false;
        $this->selectedSetlist->load('songs');
    }

    private function reorderPositions(): void
    {
        $songs = DB::table('setlist_song')
            ->where('setlist_id', $this->selectedSetlist->id)
            ->orderBy('position')
            ->get();

        foreach ($songs as $index => $pivot) {
            DB::table('setlist_song')
                ->where('id', $pivot->id)
                ->update(['position' => $index + 1]);
        }
    }

    public function render(): View
    {
        $setlists = Setlist::withCount('songs')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $availableSongs = collect();
        $availableKeys = ChordCatalog::keys();
        if ($this->showSongPickerModal) {
            $alreadyAddedIds = $this->selectedSetlist
                ? $this->selectedSetlist->songs->pluck('id')->toArray()
                : [];

            $availableSongs = Song::query()
                ->whereNotIn('id', $alreadyAddedIds)
                ->when($this->searchSong, function ($query) {
                    $query->where(function ($searchQuery) {
                        $searchQuery->where('title', 'like', '%' . $this->searchSong . '%')
                            ->orWhere('artist', 'like', '%' . $this->searchSong . '%');
                    });
                })
                ->take(10)
                ->get();
        }

        return view('livewire.setlist-manager', [
            'setlists' => $setlists,
            'availableSongs' => $availableSongs,
            'availableKeys' => $availableKeys,
        ]);
    }
}
