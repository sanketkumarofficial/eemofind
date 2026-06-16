<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_mobile_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'mobile' => '9876543210',
            'password' => 'Secure@123',
            'password_confirmation' => 'Secure@123',
            'device_name' => 'Android',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
        $this->assertDatabaseHas('users', ['email' => 'mobile@example.com', 'status' => 'active']);
    }

    public function test_suspended_user_cannot_login_to_api(): void
    {
        User::factory()->create(['email' => 'blocked@example.com', 'password' => 'Secure@123', 'status' => 'suspended']);

        $this->postJson('/api/v1/auth/login', ['email' => 'blocked@example.com', 'password' => 'Secure@123', 'device_name' => 'Android'])
            ->assertForbidden();
    }

    public function test_email_inputs_reject_crlf_control_characters(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Unsafe User',
            'email' => "unsafe@example.com\r\nBcc:attacker@example.com",
            'password' => 'Secure@123',
            'password_confirmation' => 'Secure@123',
            'device_name' => 'Android',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
