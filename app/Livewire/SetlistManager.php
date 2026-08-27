<?php

namespace App\Livewire;

use App\Models\Setlist;
use App\Models\Song;
use Illuminate\Support\Facades\DB;
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

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'scheduled_at' => 'nullable|date',
    ];

    public function mount(?int $setlistId = null)
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
            'user_id' => auth()->id() ?? 1,
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

        $song = Song::find($songId);
        if (! $song) {
            return;
        }

        $maxPosition = DB::table('setlist_song')
            ->where('setlist_id', $this->selectedSetlist->id)
            ->max('position') ?? 0;

        $this->selectedSetlist->songs()->attach($songId, [
            'position' => $maxPosition + 1,
            'custom_key' => $song->original_key,
            'notes' => '',
        ]);

        $this->selectedSetlist->load('songs');
    }

    public function removeSongFromSetlist(int $songId): void
    {
        if (! $this->selectedSetlist) {
            return;
        }

        $this->selectedSetlist->songs()->detach($songId);
        $this->reorderPositions();
        $this->selectedSetlist->load('songs');
    }

    public function moveSong(int $songId, string $direction): void
    {
        if (! $this->selectedSetlist) {
            return;
        }

        $songs = $this->selectedSetlist->songs()->orderBy('pivot_position')->get();
        $currentIndex = $songs->search(fn ($s) => $s->id === $songId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex >= 0 && $targetIndex < $songs->count()) {
            $currentSong = $songs[$currentIndex];
            $targetSong = $songs[$targetIndex];

            // Intercambiar posiciones en la tabla pivote
            $tempPos = $currentSong->pivot->position;

            DB::table('setlist_song')
                ->where('id', $currentSong->pivot->id)
                ->update(['position' => $targetSong->pivot->position]);

            DB::table('setlist_song')
                ->where('id', $targetSong->pivot->id)
                ->update(['position' => $tempPos]);

            $this->selectedSetlist->load('songs');
        }
    }

    public function openPivotModal(int $pivotId, string $currentKey, ?string $currentNotes): void
    {
        $this->editingPivotId = $pivotId;
        $this->customKey = $currentKey;
        $this->notes = $currentNotes ?? '';
        $this->showPivotModal = true;
    }

    public function updatePivotDetails(): void
    {
        if (! $this->editingPivotId) {
            return;
        }

        DB::table('setlist_song')
            ->where('id', $this->editingPivotId)
            ->update([
                'custom_key' => $this->customKey,
                'notes' => $this->notes,
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

    public function render()
    {
        $setlists = Setlist::withCount('songs')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $availableSongs = [];
        if ($this->showSongPickerModal) {
            $alreadyAddedIds = $this->selectedSetlist
                ? $this->selectedSetlist->songs->pluck('id')->toArray()
                : [];

            $availableSongs = Song::query()
                ->whereNotIn('id', $alreadyAddedIds)
                ->when($this->searchSong, function ($query) {
                    $query->where('title', 'like', '%'.$this->searchSong.'%')
                        ->orWhere('artist', 'like', '%'.$this->searchSong.'%');
                })
                ->take(10)
                ->get();
        }

        return view('livewire.setlist-manager', [
            'setlists' => $setlists,
            'availableSongs' => $availableSongs,
        ]);
    }
}
