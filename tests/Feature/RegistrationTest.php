<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario Outline: Successful registration
     *
     * Given the request body is:
     * {
     *   "email": "backend@multisyscorp.com",
     *   "password": "test123"
     * }
     * When I request /register using POST
     * Then the response header http code is 201
     * And the response body is:
     * {
     *   "message": "User successfully registered"
     * }
     */
    public function test_successful_registration(): void
    {
        Queue::fake();

        $payload = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123',
        ];

        $response = $this->postJson('/register', $payload);

        $response->assertStatus(201)
            ->assertExactJson([
                'message' => 'User successfully registered',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'backend@multisyscorp.com',
        ]);

        // Verify bonus requirement: asynchronous email job was dispatched
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) {
            return $job->user->email === 'backend@multisyscorp.com';
        });
    }

    /**
     * Scenario Outline: Successful registration via /api/register prefix
     */
    public function test_successful_registration_via_api_prefix(): void
    {
        Queue::fake();

        $payload = [
            'email' => 'api_user@multisyscorp.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertExactJson([
                'message' => 'User successfully registered',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'api_user@multisyscorp.com',
        ]);
    }

    /**
     * Scenario Outline: Unsuccessful registration due to email is already taken
     *
     * Given the request body is:
     * {
     *   "email": "backend@multisyscorp.com",
     *   "password": "test123"
     * }
     * When I request /register using POST
     * Then the response header http code is 400
     * And the response body is:
     * {
     *   "message": "Email already taken"
     * }
     */
    public function test_unsuccessful_registration_due_to_email_is_already_taken(): void
    {
        // Pre-create the user
        User::create([
            'email' => 'backend@multisyscorp.com',
            'password' => bcrypt('existing_password'),
            'name' => 'Existing User',
        ]);

        $payload = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123',
        ];

        $response = $this->postJson('/register', $payload);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => 'Email already taken',
            ]);
    }

    /**
     * Test registration failure when required fields are missing
     */
    public function test_unsuccessful_registration_missing_fields(): void
    {
        $response = $this->postJson('/register', []);

        $response->assertStatus(400);
    }
}
