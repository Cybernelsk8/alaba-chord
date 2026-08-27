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

require __DIR__.'/settings.php';

Route::get('songs/new', SongEditor::class)->name('songs.new');
Route::get('/songs/{song}/edit', SongEditor::class)->name('songs.edit');

Route::get('setlists', SetlistManager::class)->name('setlist');

Route::get('setlists/{setlist}/view', SetlistViewer::class)->name('setlists.view');

Route::get('songs/{song}/pdf', [SongPdfController::class, 'exportSong'])->name('songs.pdf');
Route::get('setlists/{setlist}/pdf', [SongPdfController::class, 'exportSetlist'])->name('setlists.pdf');

Route::get('songs/{song}/present', SongPresenter::class)->name('songs.present');

Route::get('songs', SongManager::class)->name('songs.index');

Route::get('/songs/{song}/learn', SongLearner::class)->name('songs.learn');
