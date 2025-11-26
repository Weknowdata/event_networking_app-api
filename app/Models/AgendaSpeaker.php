<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaSpeaker extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agenda_slot_id',
        'name',
        'title',
        'company',
        'bio',
        'avatar_url',
        'sort_order',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'agenda_slot_id' => 'integer',
            'sort_order' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AgendaSlot::class, 'agenda_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
