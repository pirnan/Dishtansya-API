<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Create an order for a specific product.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $user = $request->user();
        $productId = (int) $request->input('product_id');
        $quantity = (int) $request->input('quantity');

        // Use database transaction and pessimistic locking to handle concurrent orders safely
        return DB::transaction(function () use ($user, $productId, $quantity) {
            /** @var Product|null $product */
            $product = Product::where('id', $productId)->lockForUpdate()->first();

            if (! $product) {
                return response()->json([
                    'message' => 'Failed to order this product due to product not found',
                ], 404);
            }

            // Check if available stock is sufficient
            if ($product->available_stock < $quantity) {
                return response()->json([
                    'message' => 'Failed to order this product due to unavailability of the stock',
                ], 400);
            }

            // Deduct the given quantity from available stock
            $product->decrement('available_stock', $quantity);

            // Record the order
            Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);

            return response()->json([
                'message' => 'You have successfully ordered this product.',
            ], 201);
        });
    }

    /**
     * List all products available for ordering (helper endpoint).
     *
     * @return JsonResponse
     */
    public function products(): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * List user orders (helper endpoint).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = Order::with('product')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }
}
