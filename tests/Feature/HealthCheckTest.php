<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_endpoint_is_successful(): void
    {
        $response = $this->get('/healthz');

        $response->assertOk();
    }

    public function test_json_health_endpoint_is_successful(): void
    {
        $response = $this->getJson('/health');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'app' => config('app.name'),
            ]);
    }
}
