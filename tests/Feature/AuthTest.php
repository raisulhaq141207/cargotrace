<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials_via_web(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@cargotrace.com',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'testuser@cargotrace.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@cargotrace.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'testuser@cargotrace.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_api_login_returns_valid_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'apiuser@cargotrace.com',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'apiuser@cargotrace.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'token_type',
                ]
            ]);
    }

    public function test_unauthenticated_api_request_is_rejected_with_401(): void
    {
        $response = $this->getJson('/api/shipments');
        $response->assertStatus(401);
    }

    public function test_customer_can_register_via_api(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Global Importer Inc',
            'email' => 'importer@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'company' => 'Global Importer Inc',
            'phone' => '+1 555-0199',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'importer@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);
    }
}
