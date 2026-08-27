<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SongLine extends Model
{
    protected $fillable = [
        'song_section_id',
        'position',
        'type',
        'content',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(SongSection::class, 'song_section_id');
    }
}
