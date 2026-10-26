<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_reports_ok_when_all_services_are_up(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'services' => ['database' => 'up', 'redis' => 'up'],
            ]);
    }

    public function test_reports_degraded_with_503_when_the_database_is_down(): void
    {
        config(['database.connections.sqlite.database' => '/nonexistent/lets-discuss.sqlite']);
        DB::purge('sqlite');

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('services.database', 'down')
            ->assertJsonPath('services.redis', 'up');
    }

    public function test_reports_degraded_with_503_when_redis_is_down(): void
    {
        config([
            'database.redis.default.host' => '127.0.0.1',
            'database.redis.default.port' => 1,
        ]);
        Redis::purge('default');

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('services.database', 'up')
            ->assertJsonPath('services.redis', 'down');
    }

    public function test_spa_requests_still_get_503_when_the_redis_session_store_is_down(): void
    {
        // Requests from the SPA are "stateful" (Sanctum), so a Redis-backed session would start
        // before the controller runs and turn the health answer into a 500.
        config([
            'session.driver' => 'redis',
            'database.redis.default.host' => '127.0.0.1',
            'database.redis.default.port' => 1,
        ]);
        Redis::purge('default');

        $this->withHeaders([
            'Origin' => 'http://lets-discuss.localhost',
            'Referer' => 'http://lets-discuss.localhost/',
        ])->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('services.redis', 'down');
    }
}
