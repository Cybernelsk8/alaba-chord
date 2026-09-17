<?php

use App\Http\Controllers\SongPdfController;
use App\Livewire\SetlistManager;
use App\Livewire\SetlistViewer;
use App\Livewire\SongEditor;
use App\Livewire\SongLearner;
use App\Livewire\SongManager;
use App\Livewire\SongPresenter;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/settings.php';

Route::livewire('songs/new', SongEditor::class)->name('songs.new');
Route::livewire('/songs/{song}/edit', SongEditor::class)->name('songs.edit');

Route::livewire('setlists', SetlistManager::class)->name('setlist');

Route::livewire('setlists/{setlist}/view', SetlistViewer::class)->name('setlists.view');

Route::get('songs/{song}/pdf', [SongPdfController::class, 'exportSong'])->name('songs.pdf');
Route::get('setlists/{setlist}/pdf', [SongPdfController::class, 'exportSetlist'])->name('setlists.pdf');

Route::livewire('songs/{song}/present', SongPresenter::class)->name('songs.present');

Route::livewire('songs', SongManager::class)->name('songs.index');

Route::livewire('/songs/{song}/learn', SongLearner::class)->name('songs.learn');
