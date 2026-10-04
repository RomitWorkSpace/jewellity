<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customers\Http\Requests\CustomerLoginRequest;
use App\Modules\Customers\Http\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('storefront.account.login');
    }

    public function login(CustomerLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate(); // new session id: prevents session fixation (the bag is kept)

        return redirect()->intended('/account');
    }

    public function showRegister(): View
    {
        return view('storefront.account.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        // Customers get no roles: only staff roles open the admin.
        $user = User::create($request->safe()->only(['name', 'email', 'phone', 'password']));

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/account')->with('status', 'Welcome to '.config('app.name').'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // The bag lives in the session, so signing out starts a clean one.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
