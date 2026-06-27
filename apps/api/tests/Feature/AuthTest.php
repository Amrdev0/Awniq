<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_login_and_fetch_profile(): void
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'test-suite',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.user.email', 'admin@awniq.test')
            ->assertJsonStructure(['data' => ['token']]);

        $token = $loginResponse->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@awniq.test');
    }

    public function test_disabled_user_cannot_login(): void
    {
        $this->seed(DatabaseSeeder::class);

        User::where('email', 'admin@awniq.test')->update(['status' => 'disabled']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
        ])->assertUnprocessable();
    }
}
