<?php

namespace AmjadIqbal\Kiln;

use AmjadIqbal\Kiln\Commands\ClearCommand;
use AmjadIqbal\Kiln\Commands\ConfigCommand;
use AmjadIqbal\Kiln\Commands\StatusCommand;
use AmjadIqbal\Kiln\Commands\WarmCommand;
use Illuminate\Support\ServiceProvider;

class KilnServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kiln.php', 'kiln');

        $this->app->singleton(OpcacheManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kiln.php' => config_path('kiln.php'),
            ], 'kiln-config');

            $this->commands([
                ClearCommand::class,
                WarmCommand::class,
                StatusCommand::class,
                ConfigCommand::class,
            ]);
        }

        if (config('kiln.route.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}
