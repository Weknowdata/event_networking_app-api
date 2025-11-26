<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaSlot extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agenda_day_id',
        'start_time',
        'end_time',
        'title',
        'description',
        'location',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'agenda_day_id' => 'integer',
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(AgendaDay::class, 'agenda_day_id');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(AgendaSpeaker::class)->orderBy('sort_order');
    }
}
