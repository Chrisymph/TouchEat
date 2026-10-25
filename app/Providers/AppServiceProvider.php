<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @param UrlGenerator $url
     * @return void
     */
    public function boot(UrlGenerator $url)
    {
        // Forcer HTTPS en production sur Render
        if (app()->environment('production')) {
            $url->forceScheme('https');
        }

        // Composant Button
        Blade::component('components.ui.button', 'ui-button');

        // Composant Input
        Blade::component('components.ui.input', 'ui-input');

        // Composant Card
        Blade::component('components.ui.card', 'ui-card');
    }
}
