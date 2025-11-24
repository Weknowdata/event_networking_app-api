<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserProfileSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $attendees = [
            [
                'name' => 'Emma Rivera',
                'email' => 'emma.rivera@example.com',
                'job_title' => 'Product Marketing Lead',
                'company_name' => 'EventSphere Inc.',
                'location' => 'San Francisco, CA',
                'tags' => ['marketing', 'product'],
            ],
            [
                'name' => 'Lucas Patel',
                'email' => 'lucas.patel@example.com',
                'job_title' => 'Sales Lead',
                'company_name' => 'Connectify',
                'location' => 'Austin, TX',
                'tags' => ['sales', 'leads'],
            ],
            [
                'name' => 'Sophia Martinez',
                'email' => 'sophia.martinez@example.com',
                'job_title' => 'Community Manager',
                'company_name' => 'GatherWorks',
                'location' => 'Chicago, IL',
                'tags' => ['community', 'engagement'],
            ],
            [
                'name' => 'Noah Singh',
                'email' => 'noah.singh@example.com',
                'job_title' => 'Senior Event Strategist',
                'company_name' => 'Summit Studio',
                'location' => 'New York, NY',
                'tags' => ['strategy', 'events'],
            ],
            [
                'name' => 'Ava Thompson',
                'email' => 'ava.thompson@example.com',
                'job_title' => 'Marketing Manager',
                'company_name' => 'Momentum Labs',
                'location' => 'Los Angeles, CA',
                'tags' => ['marketing', 'growth'],
            ],
            [
                'name' => 'Ethan Chen',
                'email' => 'ethan.chen@example.com',
                'job_title' => 'Lead Software Engineer',
                'company_name' => 'CodeWave',
                'location' => 'Seattle, WA',
                'tags' => ['engineering', 'backend'],
            ],
            [
                'name' => 'Mia Walker',
                'email' => 'mia.walker@example.com',
                'job_title' => 'Product Manager',
                'company_name' => 'Hubverse',
                'location' => 'Boston, MA',
                'tags' => ['product', 'roadmap'],
            ],
            [
                'name' => 'Jackson Lee',
                'email' => 'jackson.lee@example.com',
                'job_title' => 'Senior Mobile Engineer',
                'company_name' => 'AppForge',
                'location' => 'Denver, CO',
                'tags' => ['mobile', 'ios'],
            ],
            [
                'name' => 'Layla Ahmed',
                'email' => 'layla.ahmed@example.com',
                'job_title' => 'Customer Success Lead',
                'company_name' => 'BrightLoop',
                'location' => 'Toronto, ON',
                'tags' => ['success', 'relationships'],
            ],
            [
                'name' => 'Mason Brooks',
                'email' => 'mason.brooks@example.com',
                'job_title' => 'Sr. Backend Engineer',
                'company_name' => 'DataPulse',
                'location' => 'Atlanta, GA',
                'tags' => ['backend', 'infra'],
            ],
        ];

        foreach ($attendees as $index => $attendee) {
            /** @var User $user */
            $user = User::firstOrCreate(
                ['email' => $attendee['email']],
                [
                    'name' => $attendee['name'],
                    'password' => Hash::make('password'),
                ],
            );

            $profileData = [
                'job_title' => $attendee['job_title'],
                'company_name' => $attendee['company_name'],
                'avatar_url' => null,
                'linkedin_url' => sprintf('https://www.linkedin.com/in/%s', Str::slug($attendee['name'])),
                'location' => $attendee['location'],
                'bio' => sprintf('Meet %s, focused on %s.', $attendee['name'], implode(', ', $attendee['tags'])),
                'phone_number' => sprintf('555000%04d', $index + 1),
                'is_first_timer' => $index % 3 === 0,
                'tags' => $attendee['tags'],
            ];

            if ($user->profile) {
                $user->profile->update($profileData);
            } else {
                $user->profile()->create($profileData);
            }
        }
    }
}
