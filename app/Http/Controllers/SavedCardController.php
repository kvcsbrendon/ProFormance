<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedCard;
use App\Rules\Luhn;
use App\Rules\CardNumberBrand;
use App\Rules\CardExpiry;
use App\Rules\CardCvv;

class SavedCardController extends Controller
{
    public function index()
    {
        $cards = SavedCard::where('user_id', Auth::user()->user_id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return view('account.saved-cards', compact('cards'));
    }

    /**
     * Add a new card.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'card_name'   => ['required', 'string', 'max:200'],
            'card_number' => ['required', 'string', 'max:30', new Luhn(), new CardNumberBrand()],
            'card_expiry' => ['required', 'string', 'max:7', new CardExpiry()],
            'card_cvv'    => ['required', 'string', 'max:4', new CardCvv($request->input('card_number'))],
        ]);

        $cardNumber = preg_replace('/\D/', '', $data['card_number']);
        $lastFour   = substr($cardNumber, -4);
        $brand      = SavedCard::detectBrand($cardNumber);

        $expParts = explode('/', $data['card_expiry']);
        $expMonth = (int) ($expParts[0] ?? 0);
        $expYear  = (int) ($expParts[1] ?? 0);
        if ($expYear < 100) $expYear += 2000;

        // Check for duplicates
        $exists = SavedCard::where('user_id', $user->user_id)
            ->where('last_four', $lastFour)
            ->where('expiry_month', $expMonth)
            ->where('expiry_year', $expYear)
            ->exists();

        if ($exists) {
            return back()->withErrors(['card_number' => 'This card is already saved.']);
        }

        $isFirst = SavedCard::where('user_id', $user->user_id)->count() === 0;

        SavedCard::create([
            'user_id'      => $user->user_id,
            'card_brand'   => $brand,
            'last_four'    => $lastFour,
            'card_name'    => $data['card_name'],
            'expiry_month' => $expMonth,
            'expiry_year'  => $expYear,
            'is_default'   => $isFirst,
        ]);

        return back()->with('success', 'Card added successfully.');
    }

    public function destroy($cardId)
    {
        $card = SavedCard::where('card_id', $cardId)
            ->where('user_id', Auth::user()->user_id)
            ->firstOrFail();

        $wasDefault = $card->is_default;
        $card->delete();

        // If deleted card was default, make the next one default
        if ($wasDefault) {
            $next = SavedCard::where('user_id', Auth::user()->user_id)->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return back()->with('success', 'Card removed.');
    }

    public function setDefault($cardId)
    {
        $user = Auth::user();

        SavedCard::where('user_id', $user->user_id)->update(['is_default' => false]);
        SavedCard::where('card_id', $cardId)
            ->where('user_id', $user->user_id)
            ->update(['is_default' => true]);

        return back()->with('success', 'Default card updated.');
    }
}