<?php

namespace App\Providers;

use App\Services\LinkedIn\Contracts\LinkedInProfileProvider;
use App\Services\LinkedIn\Providers\ApiLinkedInProvider;
use App\Services\LinkedIn\Providers\MockLinkedInProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrasi driver penyedia profil LinkedIn (Driver-Based Resolution via .env)
        $this->app->bind(
            LinkedInProfileProvider::class,
            function ($app) {
                $driver = config('services.linkedin.driver') ?: config('linkedin.driver', 'mock');

                return match ($driver) {
                    'mock' => $app->make(MockLinkedInProvider::class),
                    'api' => $app->make(ApiLinkedInProvider::class),
                    default => throw new \InvalidArgumentException(
                        "Unsupported LinkedIn driver [{$driver}]. Supported drivers are: mock, api."
                    ),
                };
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
