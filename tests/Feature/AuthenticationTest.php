<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario Outline: Successful login
     *
     * Given the request body is:
     * {
     *   "email": "backend@multisyscorp.com",
     *   "password": "test123"
     * }
     * When I request /login using POST
     * Then the response header http status code is 201
     * And the response body is:
     * {
     *   "access_token": "<JSON Web Token>"
     * }
     */
    public function test_successful_login(): void
    {
        User::create([
            'email' => 'backend@multisyscorp.com',
            'password' => Hash::make('test123'),
            'name' => 'Backend Tester',
        ]);

        $payload = [
            'email' => 'backend@multisyscorp.com',
            'password' => 'test123',
        ];

        $response = $this->postJson('/login', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
            ]);

        $token = $response->json('access_token');
        $this->assertNotEmpty($token);

        // Verify the token is a valid JSON Web Token
        $jwtService = app(JwtService::class);
        $decoded = $jwtService->validateToken($token);
        $this->assertNotNull($decoded);
        $this->assertEquals('backend@multisyscorp.com', $decoded->email);
    }

    /**
     * Test successful login via /api/login prefix
     */
    public function test_successful_login_via_api_prefix(): void
    {
        User::create([
            'email' => 'backend_api@multisyscorp.com',
            'password' => Hash::make('test123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'backend_api@multisyscorp.com',
            'password' => 'test123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['access_token']);
    }

    /**
     * Scenario Outline: Unsuccessful login due to invalid credentials
     *
     * Given the request body is:
     * {
     *   "email": "backend123123@multisyscorp.com",
     *   "password": "test121231233"
     * }
     * When I request /login using POST
     * Then the response header http status code is 401
     * And the response body is:
     * {
     *   "message": "Invalid credentials"
     * }
     */
    public function test_unsuccessful_login_due_to_invalid_credentials(): void
    {
        $payload = [
            'email' => 'backend123123@multisyscorp.com',
            'password' => 'test121231233',
        ];

        $response = $this->postJson('/login', $payload);

        $response->assertStatus(401)
            ->assertExactJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test unsuccessful login with existing user but wrong password
     */
    public function test_unsuccessful_login_with_wrong_password(): void
    {
        User::create([
            'email' => 'backend@multisyscorp.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/login', [
            'email' => 'backend@multisyscorp.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertExactJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Bonus Requirement: Account locking for 5 minutes (after 5 failed attempts)
     */
    public function test_account_locking_after_five_failed_attempts(): void
    {
        $user = User::create([
            'email' => 'lockme@multisyscorp.com',
            'password' => Hash::make('correct_password'),
        ]);

        // Attempt 5 failed logins
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/login', [
                'email' => 'lockme@multisyscorp.com',
                'password' => 'bad_pass',
            ]);
        }

        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertTrue($user->isLocked());

        // 6th attempt should be rejected due to account locking
        $lockedResponse = $this->postJson('/login', [
            'email' => 'lockme@multisyscorp.com',
            'password' => 'correct_password',
        ]);

        $lockedResponse->assertStatus(401)
            ->assertJson([
                'message' => 'Account is locked. Please try again after 5 minutes.',
            ]);
    }
}
