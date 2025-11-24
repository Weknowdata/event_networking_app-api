<?php

namespace Database\Factories;

use App\Models\PointsLog;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointsLog>
 */
class PointsLogFactory extends Factory
{
    protected $model = PointsLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_connection_id' => UserConnection::factory(),
            'source_type' => 'connection',
            'points' => 25,
            'metadata' => [],
            'awarded_at' => now(),
        ];
    }
}
