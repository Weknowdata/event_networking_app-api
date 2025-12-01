<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

class ChallengeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            [
                'code' => 'D1_CONNECT_3',
                'title' => 'Meet 3 people today',
                'description' => 'Scan or connect with three attendees.',
                'points' => 100,
                'requirements' => ['connection_made' => 3],
                'frequency' => 'daily',
                'applies_to_day' => null,
            ],
            [
                'code' => 'DAILY_SESSIONS_2',
                'title' => 'Attend 2 sessions',
                'description' => 'Check in to two workshops or talks today.',
                'points' => 200,
                'requirements' => ['session_attended' => 2],
                'frequency' => 'daily',
            ],
            [
                'code' => 'DAILY_BOOTH_3',
                'title' => 'Visit 3 sponsor booths',
                'description' => 'Scan three sponsor booth QR codes today.',
                'points' => 75,
                'requirements' => ['sponsor_booth_visited' => 3],
                'frequency' => 'daily',
            ],
            [
                'code' => 'FIRST_TIMER_FRIEND',
                'title' => 'Meet a first-timer',
                'description' => 'Connect with a first-time attendee.',
                'points' => 200,
                'requirements' => ['met_first_timer' => 1],
                'frequency' => 'daily',
            ],
        ];

        foreach ($challenges as $challenge) {
            Challenge::updateOrCreate(
                ['code' => $challenge['code']],
                array_merge(
                    $challenge,
                    [
                        'max_completions_per_user_per_day' => 1,
                        'is_enabled' => true,
                    ],
                ),
            );
        }
    }
}
