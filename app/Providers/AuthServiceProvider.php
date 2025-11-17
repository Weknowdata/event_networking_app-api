<?php

namespace App\Providers;

use App\Models\UserProfile;
use App\Policies\UserProfilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        UserProfile::class => UserProfilePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
