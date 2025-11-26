<?php

namespace Database\Seeders;

use App\Models\AgendaDay;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AgendaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = CarbonImmutable::now()->startOfDay();
        $daysCount = 5;

        $slotTemplates = [
            9 => [
                'title' => 'Welcome Coffee & Check-in',
                'description' => 'Grab coffee, pick up your badge, and meet other builders.',
                'location' => 'Lobby',
            ],
            10 => [
                'title' => 'Workshop 1: Fast Prototyping in FileMaker',
                'description' => 'Hands-on build; bring your laptop and ship a small app in 45 minutes.',
                'location' => 'Workshop Room A',
            ],
            11 => [
                'title' => 'Tea Break & Hallway Chats',
                'description' => 'Tea/coffee carts plus guided “meet 3 new people” prompts.',
                'location' => 'Lounge',
            ],
            12 => [
                'title' => 'Lunch Break & Birds-of-a-Feather Tables',
                'description' => 'Sit by topic: integrations, UX, performance, automation.',
                'location' => 'Café Pavilion',
            ],
            13 => [
                'title' => 'Workshop 2: API Integration Clinic',
                'description' => 'Connect FileMaker with REST services, webhooks, and auth flows.',
                'location' => 'Workshop Room B',
            ],
            14 => [
                'title' => 'Activity: Build-a-Bot Challenge',
                'description' => 'Teams prototype a FileMaker + AI workflow; judges pick best UX.',
                'location' => 'Collab Pods',
            ],
            15 => [
                'title' => 'Chit-Chat Networking Break',
                'description' => 'Facilitated meetups; trade QR codes and set up 1:1s.',
                'location' => 'Expo Floor',
            ],
            16 => [
                'title' => 'Showcase & Office Hours',
                'description' => 'Lightning share-outs from the challenge plus expert office hours.',
                'location' => 'Main Hall',
            ],
        ];

        $dayThemes = [
            'Day 1: Kickoff & Foundations',
            'Day 2: Integration & Automation',
            'Day 3: UX & Performance',
            'Day 4: Data Quality & Security',
            'Day 5: Deploy & Scale',
        ];

        $speakerTemplates = [
            10 => [
                [
                    'name' => 'Avery Chen',
                    'title' => 'Senior Developer',
                    'company' => 'Atlas Apps',
                ],
                [
                    'name' => 'Priya Nair',
                    'title' => 'Product Manager',
                    'company' => 'BuildFast',
                ],
            ],
            13 => [
                [
                    'name' => 'Luis Ortega',
                    'title' => 'Integration Lead',
                    'company' => 'FlowBridge',
                ],
                [
                    'name' => 'Nadia Karim',
                    'title' => 'Solutions Architect',
                    'company' => 'CloudKey',
                ],
            ],
            14 => [
                [
                    'name' => 'Mara Jensen',
                    'title' => 'Developer Advocate',
                    'company' => 'FileMaker',
                ],
            ],
            16 => [
                [
                    'name' => 'Kenji Sato',
                    'title' => 'UX Lead',
                    'company' => 'Northwind Studio',
                ],
            ],
        ];

        // Reset existing agenda so demo data stays deterministic.
        AgendaDay::query()->delete();

        $availableSpeakerIds = User::query()->inRandomOrder()->limit(8)->pluck('id')->all();

        for ($i = 0; $i < $daysCount; $i++) {
            $day = AgendaDay::create([
                'day_number' => $i + 1,
                'date' => $startDate->addDays($i)->toDateString(),
            ]);

            $slots = [];
            for ($hour = 9; $hour < 17; $hour++) {
                $start = CarbonImmutable::createFromTime($hour, 0);
                $template = $slotTemplates[$hour] ?? [
                    'title' => 'TBD Session',
                    'description' => 'Session details to be announced.',
                    'location' => 'Main Hall',
                ];

                $titlePrefix = $dayThemes[$i] ?? 'Conference Day';

                $slots[] = [
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $start->addHour()->format('H:i:s'),
                    'title' => "{$titlePrefix} — {$template['title']}",
                    'description' => $template['description'],
                    'location' => $template['location'],
                ];
            }

            $createdSlots = collect($day->slots()->createMany($slots));

            $createdSlots->each(function ($slot) use ($speakerTemplates, $availableSpeakerIds) {
                $hour = Carbon::parse((string) $slot->start_time)->hour;
                $templates = $speakerTemplates[$hour] ?? null;

                if (! $templates) {
                    return;
                }

                $slot->speakers()->createMany(
                    collect($templates)->values()->map(function ($speaker, $index) {
                        return [
                            'name' => $speaker['name'],
                            'title' => $speaker['title'] ?? null,
                            'company' => $speaker['company'] ?? null,
                            'bio' => $speaker['bio'] ?? null,
                            'avatar_url' => $speaker['avatar_url'] ?? null,
                            'sort_order' => $index,
                        ];
                    })->map(function ($data, $idx) use ($availableSpeakerIds) {
                        if (count($availableSpeakerIds) > 0) {
                            $data['user_id'] = $availableSpeakerIds[$idx % count($availableSpeakerIds)];
                        } else {
                            $data['user_id'] = null;
                        }

                        return $data;
                    })->all()
                );
            });
        }
    }
}
