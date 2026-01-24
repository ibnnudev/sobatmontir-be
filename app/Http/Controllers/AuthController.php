<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Kredensial yang diberikan salah.', 422);
        }
        if (!$user->is_active) {
            return ApiResponse::error('Akun Anda telah dinonaktifkan.', 422);
        }

        $user->tokens()->delete();
        $token = $user->createToken('API Token')->plainTextToken;
        $responseData = [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ];

        return ApiResponse::success($responseData, 'Login berhasil');
    }

    public function logout(Request $request)
    {
        // remove token
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success(null, 'Logout berhasil');
    }
}
