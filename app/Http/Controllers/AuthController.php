<?php

namespace App\Http\Controllers;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Guest user registration endpoint.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        // Check if email is already taken first (exam specification: HTTP 400)
        if ($request->filled('email') && User::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Email already taken',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            // If email already taken failed in validator
            if ($validator->errors()->has('email')) {
                foreach ($validator->errors()->get('email') as $msg) {
                    if (str_contains(strtolower($msg), 'taken') || str_contains(strtolower($msg), 'exists')) {
                        return response()->json([
                            'message' => 'Email already taken',
                        ], 400);
                    }
                }
            }

            return response()->json([
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => explode('@', $request->email)[0],
        ]);

        // Bonus: Dispatch asynchronous welcome email using Laravel Jobs & Queues
        SendWelcomeEmailJob::dispatch($user);

        return response()->json([
            'message' => 'User successfully registered',
        ], 201);
    }

    /**
     * User login / authentication endpoint.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        // If user doesn't exist, return 401 Invalid credentials
        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Bonus: Check if account is locked (5 minutes after 5 failed attempts)
        if ($user->isLocked()) {
            return response()->json([
                'message' => 'Account is locked. Please try again after 5 minutes.',
            ], 401);
        }

        // Verify password
        if (! Hash::check($request->password, $user->password)) {
            $user->incrementFailedAttempts();

            if ($user->isLocked()) {
                return response()->json([
                    'message' => 'Account is locked. Please try again after 5 minutes.',
                ], 401);
            }

            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Successful authentication: Reset failed attempts counter
        $user->resetFailedAttempts();

        // Generate JSON Web Token
        $token = $this->jwtService->generateToken($user);

        return response()->json([
            'access_token' => $token,
        ], 201);
    }
}
