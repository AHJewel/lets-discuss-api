<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerUiTest extends TestCase
{
    public function test_swagger_ui_is_served_in_the_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->get('/swagger')->assertOk();
    }

    public function test_the_yaml_spec_is_served_as_json_with_the_documented_endpoints(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->getJson('/swagger/v1')
            ->assertOk()
            ->assertJsonPath('info.title', 'Lets Discuss API')
            ->assertJsonPath('servers.0.url', 'http://api.lets-discuss.localhost')
            ->assertJsonStructure(['paths' => ['/api/health', '/sanctum/csrf-cookie']]);
    }
}
