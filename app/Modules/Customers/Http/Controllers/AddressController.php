<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Http\Requests\AddressRequest;
use App\Modules\Customers\Models\Address;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('storefront.account.addresses', ['addresses' => $request->user()->addresses()->get()]);
    }

    public function create(Request $request): View
    {
        return view('storefront.account.address-form', [
            'address' => new Address(['name' => $request->user()->name, 'phone' => $request->user()->phone]),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->addresses()->count() >= Address::MAX_PER_USER) {
            throw ValidationException::withMessages(['line1' => 'You can save up to '.Address::MAX_PER_USER.' addresses. Please remove one first.']);
        }

        DB::transaction(function () use ($request, $user) {
            $first = ! $user->addresses()->exists();
            $makeDefault = $first || $request->boolean('is_default');

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create($request->safe()->except('is_default') + ['is_default' => $makeDefault]);
        });

        return redirect()->route('account.addresses')->with('status', 'Address saved.');
    }

    public function edit(Request $request, int $address): View
    {
        return view('storefront.account.address-form', ['address' => $this->owned($request, $address)]);
    }

    public function update(AddressRequest $request, int $address): RedirectResponse
    {
        $model = $this->owned($request, $address);

        DB::transaction(function () use ($request, $model) {
            $makeDefault = $model->is_default || $request->boolean('is_default'); // a default can only be moved by choosing another

            if ($makeDefault) {
                $request->user()->addresses()->whereKeyNot($model->id)->update(['is_default' => false]);
            }

            $model->update($request->safe()->except('is_default') + ['is_default' => $makeDefault]);
        });

        return redirect()->route('account.addresses')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, int $address): RedirectResponse
    {
        $model = $this->owned($request, $address);

        DB::transaction(function () use ($request, $model) {
            $wasDefault = $model->is_default;
            $model->delete();

            if ($wasDefault) {
                $request->user()->addresses()->oldest('id')->first()?->update(['is_default' => true]);
            }
        });

        return redirect()->route('account.addresses')->with('status', 'Address removed.');
    }

    public function makeDefault(Request $request, int $address): RedirectResponse
    {
        $model = $this->owned($request, $address);

        DB::transaction(function () use ($request, $model) {
            $request->user()->addresses()->update(['is_default' => false]);
            $model->update(['is_default' => true]);
        });

        return back()->with('status', 'Default address updated.');
    }

    /** The signed-in customer's own address, or 404 (never reveals other people's ids). */
    private function owned(Request $request, int $id): Address
    {
        return $request->user()->addresses()->whereKey($id)->firstOrFail();
    }
}
