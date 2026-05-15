<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;

class AccountAddressController extends Controller
{
    public function index()
    {
        $countries = \Illuminate\Support\Facades\DB::table('countries')
        ->orderBy('country_name')
        ->pluck('country_name', 'country_code');
        $addresses = Address::where('user_id', Auth::user()->user_id)
            ->orderByDesc('is_default_shipping_address')
            ->get();

        return view('account.addresses', compact('addresses', 'countries'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'recipient_name'   => 'required|string|max:200',
            'house_number'     => 'required|string|max:10',
            'address_line_one' => 'required|string|max:100',
            'address_line_two' => 'nullable|string|max:100',
            'city'             => 'required|string|max:100',
            'county'           => 'nullable|string|max:100',
            'postcode'         => 'required|string|max:100',
            'country_code'     => 'required|string|max:2',
            'country_phone_code' => 'required|integer',
            'phone_number'     => 'nullable|string|max:20',
            'is_default_shipping_address' => 'sometimes|boolean',
            'is_default_billing_address'  => 'sometimes|boolean',
        ]);

        $data['user_id'] = $user->user_id;

        // If setting as default, unset other defaults first
        if (!empty($data['is_default_shipping_address'])) {
            Address::where('user_id', $user->user_id)
                ->update(['is_default_shipping_address' => false]);
        }

        if (!empty($data['is_default_billing_address'])) {
            Address::where('user_id', $user->user_id)
                ->update(['is_default_billing_address' => false]);
        }

        Address::create($data);

        return redirect()->route('account.addresses')
            ->with('success', 'Address added successfully.');
    }

    public function update(Request $request, $addressId)
    {
        $user = Auth::user();
        $address = Address::where('user_id', $user->user_id)
            ->where('address_id', $addressId)
            ->firstOrFail();

        $data = $request->validate([
            'recipient_name'   => 'required|string|max:200',
            'house_number'     => 'required|string|max:10',
            'address_line_one' => 'required|string|max:100',
            'address_line_two' => 'nullable|string|max:100',
            'city'             => 'required|string|max:100',
            'county'           => 'nullable|string|max:100',
            'postcode'         => 'required|string|max:100',
            'country_code'     => 'required|string|max:2',
            'country_phone_code' => 'required|integer',
            'phone_number'     => 'nullable|string|max:20',
            'is_default_shipping_address' => 'sometimes|boolean',
            'is_default_billing_address'  => 'sometimes|boolean',
        ]);

        if (!empty($data['is_default_shipping_address'])) {
            Address::where('user_id', $user->user_id)
                ->where('address_id', '!=', $addressId)
                ->update(['is_default_shipping_address' => false]);
        }

        if (!empty($data['is_default_billing_address'])) {
            Address::where('user_id', $user->user_id)
                ->where('address_id', '!=', $addressId)
                ->update(['is_default_billing_address' => false]);
        }

        $address->update($data);

        return redirect()->route('account.addresses')
            ->with('success', 'Address updated.');
    }

    public function destroy($addressId)
    {
        $user = Auth::user();
        Address::where('user_id', $user->user_id)
            ->where('address_id', $addressId)
            ->firstOrFail()
            ->delete();

        return redirect()->route('account.addresses')
            ->with('success', 'Address removed.');
    }
}
