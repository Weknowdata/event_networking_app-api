<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Attaches a profile to every user without one.
 */
class UserProfileDemoSeeder extends Seeder
{
    public function __construct(private readonly Generator $faker)
    {
    }

    public function run(): void
    {
        $themedProfiles = $this->themedProfiles();

        User::query()
            ->doesntHave('profile')
            ->get()
            ->each(function (User $user) use ($themedProfiles) {
                $profile = $themedProfiles[$user->email] ?? null;
                $company = $profile['company_name'] ?? $this->faker->company();
                $jobTitle = $profile['job_title'] ?? $this->faker->jobTitle();
                $location = $profile['location'] ?? $this->faker->city();
                $tags = $profile['tags'] ?? [$this->faker->word(), $this->faker->word()];

                $user->profile()->create([
                    'job_title' => $jobTitle,
                    'company_name' => $company,
                    'avatar_url' => null,
                    'linkedin_url' => sprintf('https://www.linkedin.com/in/%s', Str::slug($user->name)),
                    'location' => $location,
                    'bio' => sprintf('%s at %s, focused on %s.', $jobTitle, $company, implode(', ', $tags)),
                    'phone_number' => $this->faker->phoneNumber(),
                    'is_first_timer' => $profile['is_first_timer'] ?? $this->faker->boolean(30),
                    'tags' => $tags,
                ]);
            });
    }

    /**
     * Provide themed profile data keyed by email for known demo users.
     *
     * @return array<string, array<string, mixed>>
     */
    private function themedProfiles(): array
    {
        return [
            'test@example.com' => [
                'job_title' => 'QA Engineer',
                'company_name' => 'Demo Org',
                'location' => 'Remote',
                'tags' => ['testing', 'demo'],
                'is_first_timer' => true,
            ],
            'ada@example.com' => [
                'job_title' => 'ML Researcher',
                'company_name' => 'Analytical Engines',
                'location' => 'London, UK',
                'tags' => ['ml', 'math'],
                'is_first_timer' => false,
            ],
            'grace@example.com' => [
                'job_title' => 'Compiler Engineer',
                'company_name' => 'COBOL Labs',
                'location' => 'Arlington, VA',
                'tags' => ['compilers', 'navy'],
                'is_first_timer' => false,
            ],
            'linus@example.com' => [
                'job_title' => 'Kernel Lead',
                'company_name' => 'Open Source Collective',
                'location' => 'Portland, OR',
                'tags' => ['linux', 'git'],
                'is_first_timer' => false,
            ],
            'margaret@example.com' => [
                'job_title' => 'Aerospace Engineer',
                'company_name' => 'Apollo Systems',
                'location' => 'Cambridge, MA',
                'tags' => ['safety', 'mission'],
                'is_first_timer' => false,
            ],
            'guido@example.com' => [
                'job_title' => 'Distinguished Engineer',
                'company_name' => 'PyWorks',
                'location' => 'San Francisco, CA',
                'tags' => ['python', 'core'],
                'is_first_timer' => false,
            ],
            'bjarne@example.com' => [
                'job_title' => 'Language Designer',
                'company_name' => 'C++ Institute',
                'location' => 'New York, NY',
                'tags' => ['c++', 'standards'],
                'is_first_timer' => false,
            ],
            'ken@example.com' => [
                'job_title' => 'Systems Engineer',
                'company_name' => 'Unix Labs',
                'location' => 'Murray Hill, NJ',
                'tags' => ['unix', 'go'],
                'is_first_timer' => false,
            ],
            'tim@example.com' => [
                'job_title' => 'Web Inventor',
                'company_name' => 'World Wide Web Consortium',
                'location' => 'Boston, MA',
                'tags' => ['web', 'standards'],
                'is_first_timer' => false,
            ],
            'radia@example.com' => [
                'job_title' => 'Network Engineer',
                'company_name' => 'BridgeNet',
                'location' => 'Boston, MA',
                'tags' => ['networking', 'security'],
                'is_first_timer' => false,
            ],
            'brendan@example.com' => [
                'job_title' => 'Founder',
                'company_name' => 'JS Labs',
                'location' => 'Mountain View, CA',
                'tags' => ['javascript', 'browsers'],
                'is_first_timer' => false,
            ],
            'mitchell@example.com' => [
                'job_title' => 'Executive Chair',
                'company_name' => 'Open Web Alliance',
                'location' => 'San Francisco, CA',
                'tags' => ['mozilla', 'openweb'],
                'is_first_timer' => false,
            ],
            'matsumoto@example.com' => [
                'job_title' => 'Chief Architect',
                'company_name' => 'Ruby Core',
                'location' => 'Matsue, JP',
                'tags' => ['ruby', 'language'],
                'is_first_timer' => false,
            ],
            'hadi@example.com' => [
                'job_title' => 'Developer Advocate',
                'company_name' => 'JetBrains',
                'location' => 'Barcelona, ES',
                'tags' => ['kotlin', 'advocacy'],
                'is_first_timer' => false,
            ],
            'lea@example.com' => [
                'job_title' => 'CSS Specialist',
                'company_name' => 'Design Systems Co',
                'location' => 'Athens, GR',
                'tags' => ['css', 'web'],
                'is_first_timer' => true,
            ],
            'kent@example.com' => [
                'job_title' => 'Principal Engineer',
                'company_name' => 'XP Labs',
                'location' => 'Portland, OR',
                'tags' => ['tdd', 'agile'],
                'is_first_timer' => false,
            ],
            'sarah@example.com' => [
                'job_title' => 'VP Developer Experience',
                'company_name' => 'Jamstack Co',
                'location' => 'Denver, CO',
                'tags' => ['vue', 'svg'],
                'is_first_timer' => true,
            ],
            'dan@example.com' => [
                'job_title' => 'React Core',
                'company_name' => 'Meta',
                'location' => 'London, UK',
                'tags' => ['react', 'ux'],
                'is_first_timer' => true,
            ],
            'kelsey@example.com' => [
                'job_title' => 'Staff Developer Advocate',
                'company_name' => 'K8s Co',
                'location' => 'Portland, OR',
                'tags' => ['kubernetes', 'cloud'],
                'is_first_timer' => false,
            ],
            'charity@example.com' => [
                'job_title' => 'CTO',
                'company_name' => 'Observability Inc.',
                'location' => 'San Francisco, CA',
                'tags' => ['observability', 'sre'],
                'is_first_timer' => true,
            ],
            'gaurav@example.com' => [
                'job_title' => 'Platform Engineer',
                'company_name' => 'DevOps Studio',
                'location' => 'Bengaluru, IN',
                'tags' => ['platform', 'devops'],
                'is_first_timer' => true,
            ],
        ];
    }
}
