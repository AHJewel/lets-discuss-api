<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaAuthenticationTest extends TestCase
{
    private const SPA_ORIGIN = 'http://lets-discuss.localhost';

    public function test_csrf_cookie_is_issued_for_the_shared_parent_domain(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::SPA_ORIGIN,
            'Referer' => self::SPA_ORIGIN.'/',
        ])->get('/sanctum/csrf-cookie');

        $response->assertNoContent();

        $cookie = $response->getCookie('XSRF-TOKEN', decrypt: false);
        $this->assertNotNull($cookie, 'XSRF-TOKEN cookie was not set');
        $this->assertStringEndsWith('lets-discuss.localhost', (string) $cookie->getDomain());
    }

    public function test_spa_origin_may_make_credentialed_cross_origin_requests(): void
    {
        $this->withHeaders([
            'Origin' => self::SPA_ORIGIN,
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/health')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::SPA_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_unknown_origins_are_not_granted_cors_access(): void
    {
        // With a single allowed origin, php-cors always answers with that fixed origin,
        // so the browser rejects any other caller; it must never echo the caller or use "*".
        $allowed = $this->withHeaders([
            'Origin' => 'http://evil.example',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/health')
            ->headers->get('Access-Control-Allow-Origin');

        $this->assertNotContains($allowed, ['http://evil.example', '*']);
    }
}
