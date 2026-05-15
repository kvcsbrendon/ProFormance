<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SubscriptionPayment;

class PaymentHistoryController extends Controller
{
    /**
     * Subscription payment history.
     */
    public function index()
    {
        $user = Auth::user();

        $payments = SubscriptionPayment::where('user_id', $user->user_id)
            ->with(['plan', 'savedCard'])
            ->orderByDesc('created_at')
            ->paginate(20);

        // Summary stats
        $totalPaid = SubscriptionPayment::where('user_id', $user->user_id)
            ->where('status', 'Paid')
            ->sum('amount_penny');

        $paymentCount = SubscriptionPayment::where('user_id', $user->user_id)->count();

        $symbol = $this->currencySymbol();

        return view('account.payment-history', compact('payments', 'totalPaid', 'paymentCount', 'symbol'));
    }

    private function currencySymbol(): string
    {
        $symbols = ['gbp' => '£', 'usd' => '$', 'eur' => '€'];
        return $symbols[session('currency', 'gbp')] ?? '£';
    }
}