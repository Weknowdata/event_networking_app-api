<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserConnection extends Model
{
    use HasFactory;

    // Point values granted for connecting with first-time vs returning attendees.
    public const FIRST_TIMER_POINTS = 50;
    public const RETURNING_POINTS = 25;

    protected $fillable = [
        'user_id',
        'attendee_id',
        'pair_token',
        'is_first_timer',
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

    public function pointsLogs(): HasMany
    {
        return $this->hasMany(PointsLog::class);
    }
}
