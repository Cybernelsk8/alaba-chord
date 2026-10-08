<?php

namespace App\Livewire;

use App\Models\Song;
use App\Services\ChordPro\ChordCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SongManager extends Component
{
    use WithPagination;

    // Filtros de búsqueda
    public string $search = '';

    public string $filterKey = '';

    // Modal de Creación Rápida
    public bool $showCreateModal = false;

    public string $title = '';

    public string $artist = '';

    public string $original_key = 'C';

    public ?int $tempo = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterKey(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['title', 'artist', 'original_key', 'tempo']);
        $this->showCreateModal = true;
    }

    public function createSong(): RedirectResponse
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'original_key' => ['required', 'string', Rule::in(ChordCatalog::keys())],
            'tempo' => 'nullable|integer|min:30|max:300',
        ]);

        $song = Song::create([
            'user_id' => Auth::id() ?? 1,
            'title' => $this->title,
            'artist' => $this->artist,
            'original_key' => $this->original_key,
            'tempo' => $this->tempo,
        ]);

        $this->showCreateModal = false;

        // Redireccionamos directamente al editor visual para estructurar la canción
        return redirect()->route('songs.edit', $song);
    }

    public function deleteSong(int $songId): void
    {
        Song::destroy($songId);
    }

    public function render(): View
    {
        $songs = Song::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('artist', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterKey, function ($query) {
                $query->where('original_key', $this->filterKey);
            })
            ->orderBy('title', 'asc')
            ->paginate(12);

        // Lista de tonos para el selector de filtro
        $availableKeys = ChordCatalog::keys();

        return view('livewire.song-manager', [
            'songs' => $songs,
            'availableKeys' => $availableKeys,
        ]);
    }
}
