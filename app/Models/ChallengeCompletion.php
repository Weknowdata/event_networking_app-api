<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeCompletion extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'challenge_id',
        'user_id',
        'event_day',
        'awarded_points',
        'completed_at',
        'source',
        'details',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_day' => 'date',
            'awarded_points' => 'integer',
            'completed_at' => 'datetime',
            'details' => 'array',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
