<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias(
            \Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\RulesMapper::class,
            \App\Overwrites\RulesMapper::class
        );

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
