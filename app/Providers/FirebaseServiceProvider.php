<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirebaseAuth::class, function () {
            $factory = (new Factory)
                ->withServiceAccount(base_path(config('firebase.credentials')))
                ->withProjectId(config('firebase.project_id'));

            return $factory->createAuth();
        });
    }

    public function boot(): void
    {
        //
    }
}
