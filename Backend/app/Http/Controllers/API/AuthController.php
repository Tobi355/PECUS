<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Use database transaction for atomic operation
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'surname' => $request->surname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $defaultCategories = [
                ['name' => 'Alimentación', 'type' => 'EXPENSE', 'icon' => 'utensils', 'color' => '#dc3545'],
                ['name' => 'Transporte', 'type' => 'EXPENSE', 'icon' => 'car', 'color' => '#ffc107'],
                ['name' => 'Vivienda', 'type' => 'EXPENSE', 'icon' => 'home', 'color' => '#6f42c1'],
                ['name' => 'Servicios', 'type' => 'EXPENSE', 'icon' => 'zap', 'color' => '#20c997'],
                ['name' => 'Salud', 'type' => 'EXPENSE', 'icon' => 'heart-pulse', 'color' => '#fd7e14'],
                ['name' => 'Otros gastos', 'type' => 'EXPENSE', 'icon' => 'more-horizontal', 'color' => '#6c757d'],
                ['name' => 'Sueldo', 'type' => 'INCOME', 'icon' => 'banknote', 'color' => '#28a745'],
                ['name' => 'Otros ingresos', 'type' => 'INCOME', 'icon' => 'more-horizontal', 'color' => '#20c997'],
            ];
            foreach ($defaultCategories as $category) {
                $user->categories()->create(array_merge($category, ['is_default' => true]));
            }

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke the token).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}