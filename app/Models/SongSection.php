<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SongSection extends Model
{
    protected $fillable = [
        'song_id',
        'type',
        'label',
        'position',
        'repeats_section_id',
    ];

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SongLine::class)->orderBy('position');
    }

    public function repeatedSection(): BelongsTo
    {
        return $this->belongsTo(SongSection::class, 'repeats_section_id');
    }
}
