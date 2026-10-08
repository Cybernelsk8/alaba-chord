<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Setlist extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'date',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Song, $this> */
    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class)
            ->withPivot(['id', 'position', 'custom_key', 'notes'])
            ->orderByPivot('position');
    }
}
