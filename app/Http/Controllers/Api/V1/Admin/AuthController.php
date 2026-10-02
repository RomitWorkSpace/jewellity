<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        // Prevent session fixation.
        $request->session()->regenerate();

        return response()->json($this->payload($user));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    /** @return array<string, mixed> */
    private function payload(User $user): array
    {
        return [
            'user' => $user->only(['id', 'name', 'email']),
            'roles' => $user->getRoleNames(),
            'is_super_admin' => $user->isSuperAdmin(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
