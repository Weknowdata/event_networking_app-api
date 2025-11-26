<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'title',
        'description',
        'points',
        'requirements',
        'frequency',
        'max_completions_per_user_per_day',
        'applies_to_day',
        'active_start',
        'active_end',
        'is_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'points' => 'integer',
            'max_completions_per_user_per_day' => 'integer',
            'applies_to_day' => 'integer',
            'active_start' => 'date',
            'active_end' => 'date',
            'is_enabled' => 'boolean',
        ];
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ChallengeCompletion::class);
    }
}
