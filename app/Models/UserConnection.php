<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendee_id',
        'pair_token',
        'is_first_timer',
        'base_points',
        'total_points',
        'user_notes_added',
        'user_notes',
        'attendee_notes_added',
        'attendee_notes',
        'connected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_first_timer' => 'boolean',
            'user_notes_added' => 'boolean',
            'attendee_notes_added' => 'boolean',
            'connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendee_id');
    }
}
