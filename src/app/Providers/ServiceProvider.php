<?php

namespace AMoschou\TDev\Messaging\App\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
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
        $this->mergeConfigFrom($this->path('config/public.php'), 'tdev_messaging');

        // $this->publishes([
        //     $this->path('config/public.php') => config_path('tdev_messaging.php'),
        // ]);
    }

    private function path(string $path): string
    {
        return __DIR__ . '/../../' . $path;
    }
}