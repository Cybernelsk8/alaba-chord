<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, SongSection> $sections
 * @property array<string, mixed>|null $meta
 */
class Song extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'subtitle',
        'artist',
        'original_key',
        'capo',
        'time_signature',
        'tempo',
        'duration',
        'meta',
        'notes',
    ];

    protected $casts = [
        'meta' => 'array',
        'capo' => 'integer',
        'tempo' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SongSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(SongSection::class)->orderBy('position');
    }

    public function songbooks(): BelongsToMany
    {
        return $this->belongsToMany(Songbook::class, 'songbook_song')
            ->withPivot('position', 'transpose_key')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function setlists(): BelongsToMany
    {
        return $this->belongsToMany(Setlist::class)
            ->withPivot(['id', 'position', 'custom_key', 'notes']);
    }
}
