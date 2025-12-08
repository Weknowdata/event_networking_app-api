<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'rating',
        'comment',
    ];

    /**
     * @return BelongsTo<User, Feedback>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
