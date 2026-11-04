<?php

namespace App\Providers;

use App\Contracts\FaceVerifier;
use App\Services\Face\FakeFaceVerifier;
use App\Services\Face\HttpFaceVerifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FaceVerifier::class, function () {
            $driver = config('humify.face_verifier.driver', 'fake');

            if ($driver === 'http') {
                return $this->app->make(HttpFaceVerifier::class);
            }

            return $this->app->make(FakeFaceVerifier::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
