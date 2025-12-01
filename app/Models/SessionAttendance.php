<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'agenda_slot_id',
        'event_day',
        'checked_in_at',
        'checked_out_at',
        'source',
        'device_id',
        'valid_for_points',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_day' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'valid_for_points' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AgendaSlot::class, 'agenda_slot_id');
    }
}
