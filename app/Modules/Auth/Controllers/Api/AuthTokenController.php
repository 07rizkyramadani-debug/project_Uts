<?php

namespace Modules\Auth\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Requests\ApiLoginRequest;
use Modules\Auth\Requests\RegisterRequest;

/**
 * Autentikasi berbasis token (Laravel Sanctum personal access token).
 */
class AuthTokenController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // di-hash otomatis oleh cast "hashed"
            'role' => User::ROLE_USER,
        ]);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('api-register')->plainTextToken,
        ], 201);
    }

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken($request->input('device_name', 'api'))->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token berhasil dicabut (logout).']);
    }

    private function userPayload(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'role']);
    }
}
