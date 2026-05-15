<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function updateCurrency(Request $request)
    {
        $currency = $request->input('currency');
        $allowed = ['gbp', 'eur', 'usd'];

        if (in_array($currency, $allowed)) {
            session(['currency' => $currency]);
        }

        return back();
    }

    public function updateLocale(Request $request)
    {
        $locale = $request->input('locale');
        $allowed = ['en', 'es', 'de'];

        if (in_array($locale, $allowed)) {
            session(['locale' => $locale]);
        }

        return back();
    }
}
