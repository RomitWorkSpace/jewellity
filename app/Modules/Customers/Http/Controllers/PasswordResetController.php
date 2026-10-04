<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('storefront.account.forgot-password');
    }

    /** Always answers the same way, so this cannot be used to discover which emails have accounts. */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => mb_strtolower(trim($request->input('email')))]);

        return back()->with('status', 'If an account exists for that email, we have sent a link to reset the password.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('storefront.account.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            ['email' => mb_strtolower(trim($request->input('email'))), 'password' => $request->input('password'), 'password_confirmation' => $request->input('password_confirmation'), 'token' => $request->input('token')],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
        }

        Auth::guard('web')->logout();

        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.');
    }
}
