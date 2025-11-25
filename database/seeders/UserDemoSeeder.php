<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds ~20 themed users for the developer event networking app.
 */
class UserDemoSeeder extends Seeder
{
    public function run(): void
    {
        $attendees = [
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['name' => 'Grace Hopper', 'email' => 'grace@example.com'],
            ['name' => 'Linus Torvalds', 'email' => 'linus@example.com'],
            ['name' => 'Margaret Hamilton', 'email' => 'margaret@example.com'],
            ['name' => 'Guido van Rossum', 'email' => 'guido@example.com'],
            ['name' => 'Bjarne Stroustrup', 'email' => 'bjarne@example.com'],
            ['name' => 'Ken Thompson', 'email' => 'ken@example.com'],
            ['name' => 'Tim Berners-Lee', 'email' => 'tim@example.com'],
            ['name' => 'Radia Perlman', 'email' => 'radia@example.com'],
            ['name' => 'Brendan Eich', 'email' => 'brendan@example.com'],
            ['name' => 'Mitchell Baker', 'email' => 'mitchell@example.com'],
            ['name' => 'Yukihiro Matsumoto', 'email' => 'matsumoto@example.com'],
            ['name' => 'Hadi Hariri', 'email' => 'hadi@example.com'],
            ['name' => 'Lea Verou', 'email' => 'lea@example.com'],
            ['name' => 'Kent Beck', 'email' => 'kent@example.com'],
            ['name' => 'Sarah Drasner', 'email' => 'sarah@example.com'],
            ['name' => 'Dan Abramov', 'email' => 'dan@example.com'],
            ['name' => 'Kelsey Hightower', 'email' => 'kelsey@example.com'],
            ['name' => 'Charity Majors', 'email' => 'charity@example.com'],
            ['name' => 'Gaurav Agarwal', 'email' => 'gaurav@example.com'],
        ];

        foreach ($attendees as $attendee) {
            User::firstOrCreate(
                ['email' => $attendee['email']],
                [
                    'name' => $attendee['name'],
                    'password' => Hash::make('password'),
                ],
            );
        }
    }
}
