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
}
