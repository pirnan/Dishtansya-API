<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\JwtService;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed products according to specification
        $this->seed(ProductSeeder::class);

        $this->user = User::create([
            'email' => 'backend@multisyscorp.com',
            'password' => bcrypt('test123'),
            'name' => 'Order Tester',
        ]);

        $jwtService = app(JwtService::class);
        $this->token = $jwtService->generateToken($this->user);
    }

    /**
     * Scenario Outline: Successfully created an order
     *
     * Given the request body is:
     * {
     *   "product_id": "1",
     *   "quantity": "2"
     * }
     * And the request header is:
     * Authorization: Bearer <access_token>
     * When I request /order using POST
     * Then the response header http status code is 201
     * And the response body is:
     * {
     *   "message": "You have successfully ordered this product."
     * }
     */
    public function test_successfully_created_an_order(): void
    {
        $product = Product::find(1);
        $initialStock = $product->available_stock;

        $payload = [
            'product_id' => '1',
            'quantity' => '2',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/order', $payload);

        $response->assertStatus(201)
            ->assertExactJson([
                'message' => 'You have successfully ordered this product.',
            ]);

        // Additional Requirement: deduct given quantity from available stock
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->available_stock);

        // Verify order record created in database
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'product_id' => 1,
            'quantity' => 2,
        ]);
    }

    /**
     * Test successfully created an order via /api/order prefix
     */
    public function test_successfully_created_an_order_via_api_prefix(): void
    {
        $payload = [
            'product_id' => '1',
            'quantity' => '1',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/order', $payload);

        $response->assertStatus(201)
            ->assertExactJson([
                'message' => 'You have successfully ordered this product.',
            ]);
    }

    /**
     * Scenario Outline: Unsuccessful order due to insufficient stock of a product
     *
     * Given the request body is:
     * {
     *   "product_id": "2",
     *   "quantity": "9999"
     * }
     * And the request header is:
     * Authorization: Bearer <access_token>
     * When I request /order using POST
     * Then the response header http status code is 400
     * And the response body is:
     * {
     *   "message": "Failed to order this product due to unavailability of the stock"
     * }
     */
    public function test_unsuccessful_order_due_to_insufficient_stock(): void
    {
        $product = Product::find(2);
        $initialStock = $product->available_stock;

        $payload = [
            'product_id' => '2',
            'quantity' => '9999',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/order', $payload);

        $response->assertStatus(400)
            ->assertExactJson([
                'message' => 'Failed to order this product due to unavailability of the stock',
            ]);

        // Stock should remain unchanged
        $product->refresh();
        $this->assertEquals($initialStock, $product->available_stock);

        // No order should have been created
        $this->assertDatabaseMissing('orders', [
            'product_id' => 2,
            'quantity' => 9999,
        ]);
    }

    /**
     * Test order rejection when Authorization header is missing
     */
    public function test_unauthorized_order_request_without_token(): void
    {
        $response = $this->postJson('/order', [
            'product_id' => '1',
            'quantity' => '2',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized',
            ]);
    }

    /**
     * Test order rejection with invalid token
     */
    public function test_unauthorized_order_request_with_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid.jwt.token')
            ->postJson('/order', [
                'product_id' => '1',
                'quantity' => '2',
            ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthorized',
            ]);
    }
}
