<?php

namespace Brightbulb\UptimeAgent\Tests;

use Brightbulb\UptimeAgent\UptimeAgentServiceProvider;
use Orchestra\Testbench\TestCase;

class HealthEndpointTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [UptimeAgentServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('uptime-agent.token', 'secret-token');
    }

    public function test_without_token_only_status_is_returned(): void
    {
        $this->getJson('/_uptime')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_with_token_details_are_returned(): void
    {
        $this->getJson('/_uptime', ['X-Uptime-Token' => 'secret-token'])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('platform', 'laravel')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonStructure(['versions' => ['laravel', 'php']]);
    }

    public function test_wrong_token_gets_no_details(): void
    {
        $this->getJson('/_uptime', ['X-Uptime-Token' => 'nope'])
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_database_failure_returns_503(): void
    {
        config(['database.connections.testing.database' => '/nonexistent/dir/db.sqlite']);
        app('db')->purge('testing');

        $this->getJson('/_uptime', ['X-Uptime-Token' => 'secret-token'])
            ->assertStatus(503)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('checks.database', 'error');
    }
}
