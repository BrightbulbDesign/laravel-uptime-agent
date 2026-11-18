<?php

namespace Brightbulb\UptimeAgent;

use Brightbulb\UptimeAgent\Http\HealthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UptimeAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/uptime-agent.php', 'uptime-agent');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/uptime-agent.php' => config_path('uptime-agent.php'),
        ], 'uptime-agent-config');

        // Registered without the 'web' group: no session, cookies or CSRF,
        // so the check never creates sessions or touches user state.
        Route::get(config('uptime-agent.path'), HealthController::class)
            ->name('uptime-agent.health');
    }
}
