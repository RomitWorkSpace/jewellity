<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Http\Requests\PasswordChangeRequest;
use App\Modules\Customers\Http\Requests\ProfileRequest;
use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('storefront.account.dashboard', [
            'user' => $request->user(),
            'addresses' => $request->user()->addresses()->get(),
            'recentOrders' => Order::query()->where('user_id', $request->user()->id)->with('items')->latest('id')->limit(3)->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('storefront.account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only(['name', 'email', 'phone']));

        return back()->with('status', 'Your details have been updated.');
    }

    public function updatePassword(PasswordChangeRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]); // hashed by the model cast

        // Sign out every other device/browser that held the old password.
        auth()->logoutOtherDevices($request->input('password'));
        $request->session()->regenerate();

        return back()->with('status', 'Your password has been changed.');
    }
}
