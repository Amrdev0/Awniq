<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_successful_json_response(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.service', 'awniq-api')
            ->assertJsonPath('message', 'Operation completed successfully.');
    }
}
