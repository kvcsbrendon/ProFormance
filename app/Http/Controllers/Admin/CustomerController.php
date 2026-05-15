<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('user_role', '!=', 'admin')
            ->with('loginDetail')
            ->select('users.*')
            ->selectSub(
                Order::selectRaw('COUNT(*)')->whereColumn('orders.user_id', 'users.user_id'),
                'orders_count'
            )
            ->selectSub(
                Order::selectRaw('COALESCE(SUM(total_penny), 0)')->whereColumn('orders.user_id', 'users.user_id'),
                'total_spent'
            )
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhereHas('loginDetail', fn($ld) => $ld->where('email_address', 'like', "%{$s}%"));
            });
        }

        $customers = $query->paginate(20)->withQueryString();
        return view('admin.customers.index', compact('customers'));
    }

    public function show($userId)
    {
        $customer = User::with('loginDetail')->where('user_id', $userId)->firstOrFail();
        $orders = Order::where('user_id', $userId)->with('items')->orderByDesc('created_at')->limit(20)->get();
        $totalSpent = Order::where('user_id', $userId)->sum('total_penny');
        $orderCount = Order::where('user_id', $userId)->count();
        $addresses = Address::where('user_id', $userId)->get();

        return view('admin.customers.show', compact('customer', 'orders', 'totalSpent', 'orderCount', 'addresses'));
    }

    public function toggleActive($userId)
    {
        $user = User::where('user_id', $userId)->firstOrFail();
        $user->is_active = !$user->is_active;
        $user->save();
        return back()->with('success', "Account " . ($user->is_active ? 'activated' : 'deactivated') . ".");
    }
}
