<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderBy('currency_code')->get();
        return view('admin.currencies.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'currency_code' => 'required|string|size:3|unique:currencies,currency_code',
            'currency_name' => 'required|string|max:100',
            'symbol'        => 'required|string|max:10',
            'is_active'     => 'sometimes|boolean',
        ]);
        $data['currency_code'] = strtoupper(trim($data['currency_code']));
        $data['is_active'] = $data['is_active'] ?? true;
        Currency::create($data);
        return back()->with('success', "Currency {$data['currency_code']} added.");
    }

    public function update(Request $request, $code)
    {
        $currency = Currency::where('currency_code', $code)->firstOrFail();
        $data = $request->validate([
            'currency_name' => 'required|string|max:100',
            'symbol'        => 'required|string|max:10',
            'is_active'     => 'sometimes|boolean',
        ]);
        $data['is_active'] = $data['is_active'] ?? false;
        $currency->update($data);
        return back()->with('success', "Currency {$code} updated.");
    }
}
