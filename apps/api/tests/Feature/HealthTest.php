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

    public function test_version_endpoint_returns_release_metadata(): void
    {
        config([
            'app.version' => '0.10.0',
            'app.commit' => 'test-commit',
        ]);

        $response = $this->getJson('/api/v1/version');

        $response
            ->assertOk()
            ->assertJsonPath('data.service', 'awniq-api')
            ->assertJsonPath('data.version', '0.10.0')
            ->assertJsonPath('data.commit', 'test-commit')
            ->assertJsonPath('message', 'Operation completed successfully.');
    }
}
