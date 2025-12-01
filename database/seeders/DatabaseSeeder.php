<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Seed users (20 total including a predictable test account).
        $this->call(UserDemoSeeder::class);
        // 2) Attach profiles to all users.
        $this->call(UserProfileDemoSeeder::class);
        // 3) Create demo connections between users.
        $this->call(ConnectionDemoSeeder::class);
        // 4) Seed points for those connections to drive the leaderboard.
        $this->call(LeaderboardPointsSeeder::class);
        // 5) Seed a demo 5-day agenda with 9am-5pm slots.
        $this->call(AgendaDemoSeeder::class);
        // 6) Seed demo challenges for daily play.
        $this->call(ChallengeDemoSeeder::class);
    }
}
