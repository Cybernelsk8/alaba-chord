<?php

namespace Tests\Feature;

use App\Livewire\SetlistManager;
use App\Livewire\SongEditor;
use App\Livewire\SongManager;
use App\Models\Setlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SongWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_editor_inserts_directives_and_builds_chords_from_catalog(): void
    {
        Livewire::test(SongEditor::class)
            ->call('insertChordProDirective', 'start_of_chorus')
            ->assertSet('rawChordPro', "{start_of_chorus: Coro}\n")
            ->set('selectedChordRoot', 'Db')
            ->set('selectedChordQuality', 'm7b5')
            ->set('selectedChordBass', 'F#')
            ->call('updateSelectedChordFromCatalog')
            ->assertSet('selectedChord', 'Dbm7b5/F#');
    }

    public function test_editor_rejects_section_types_not_supported_by_the_schema(): void
    {
        $song = Song::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Prueba',
        ]);

        Livewire::test(SongEditor::class, ['song' => $song])
            ->set('newSectionType', 'solo')
            ->call('addSection')
            ->assertHasErrors(['newSectionType']);

        $this->assertDatabaseCount('song_sections', 0);

        Livewire::test(SongEditor::class, ['song' => $song])
            ->set('newSectionType', 'instrumental')
            ->call('addSection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('song_sections', [
            'song_id' => $song->id,
            'type' => 'instrumental',
        ]);
    }

    public function test_setlist_song_search_does_not_return_songs_already_attached(): void
    {
        $user = User::factory()->create();
        $setlist = Setlist::create(['user_id' => $user->id, 'title' => 'Servicio']);
        $attachedSong = Song::create([
            'user_id' => $user->id,
            'title' => 'Ya agregada',
            'artist' => 'Voz Compartida',
        ]);
        $availableSong = Song::create([
            'user_id' => $user->id,
            'title' => 'Disponible',
            'artist' => 'Voz Compartida',
        ]);
        $setlist->songs()->attach($attachedSong->id, ['position' => 1]);

        Livewire::test(SetlistManager::class)
            ->call('selectSetlist', $setlist->id)
            ->set('showSongPickerModal', true)
            ->set('searchSong', 'Voz Compartida')
            ->assertViewHas('availableSongs', function ($songs): bool {
                return $songs->pluck('title')->all() === ['Disponible'];
            });
    }

    public function test_song_manager_rejects_keys_outside_the_shared_catalog(): void
    {
        Livewire::test(SongManager::class)
            ->set('title', 'Tono inválido')
            ->set('original_key', 'H')
            ->call('createSong')
            ->assertHasErrors(['original_key']);

        $this->assertDatabaseCount('songs', 0);
    }

    public function test_setlist_rejects_unknown_custom_keys_without_updating_the_pivot(): void
    {
        $user = User::factory()->create();
        $setlist = Setlist::create(['user_id' => $user->id, 'title' => 'Servicio']);
        $song = Song::create(['user_id' => $user->id, 'title' => 'Canción']);
        $setlist->songs()->attach($song->id, [
            'position' => 1,
            'custom_key' => 'D',
            'notes' => '',
        ]);
        $pivotId = DB::table('setlist_song')->value('id');

        Livewire::test(SetlistManager::class)
            ->call('selectSetlist', $setlist->id)
            ->call('openPivotModal', $pivotId, 'D', null)
            ->set('customKey', 'H')
            ->call('updatePivotDetails')
            ->assertHasErrors(['customKey']);

        $this->assertDatabaseHas('setlist_song', [
            'id' => $pivotId,
            'custom_key' => 'D',
        ]);
    }

    public function test_moving_a_setlist_song_swaps_its_position_transactionally(): void
    {
        $user = User::factory()->create();
        $setlist = Setlist::create(['user_id' => $user->id, 'title' => 'Servicio']);
        $firstSong = Song::create(['user_id' => $user->id, 'title' => 'Primera']);
        $secondSong = Song::create(['user_id' => $user->id, 'title' => 'Segunda']);
        $setlist->songs()->attach($firstSong->id, ['position' => 1]);
        $setlist->songs()->attach($secondSong->id, ['position' => 2]);

        Livewire::test(SetlistManager::class)
            ->call('selectSetlist', $setlist->id)
            ->call('moveSong', $secondSong->id, 'up');

        $this->assertDatabaseHas('setlist_song', [
            'setlist_id' => $setlist->id,
            'song_id' => $firstSong->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('setlist_song', [
            'setlist_id' => $setlist->id,
            'song_id' => $secondSong->id,
            'position' => 1,
        ]);
    }
}
