<?php

namespace App\Http\Controllers;

use App\Models\UserMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user     = Auth::user();
        $category = $request->get('tab', 'all');

        $query = UserMessage::where('user_id', $user->user_id)
            ->orderByDesc('created_at');

        if ($category !== 'all' && array_key_exists($category, UserMessage::CATEGORIES)) {
            $query->where('category', $category);
        }

        $messages = $query->paginate(15)->withQueryString();

        $unreadCounts = UserMessage::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $totalUnread = array_sum($unreadCounts);

        return view('account.messages', compact(
            'messages', 'category', 'unreadCounts', 'totalUnread'
        ));
    }

    public function markRead(UserMessage $message)
    {
        if ($message->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        if ($message->link_url) {
            return redirect($message->link_url);
        }

        return back();
    }

    public function markAllRead(Request $request)
    {
        $query = UserMessage::where('user_id', Auth::user()->user_id)
            ->where('is_read', false);

        $category = $request->get('tab');
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', 'All messages marked as read.');
    }

    /**
     * Delete a message.
     */
    public function destroy(UserMessage $message)
    {
        if ($message->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $message->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Message deleted.');
    }

    public static function checkPasswordAge(int $userId): void
    {
        $user = \App\Models\User::find($userId);
        if (!$user || !$user->password_changed_at) {
            return;
        }

        $sixMonthsAgo = now()->subMonths(6);

        if ($user->password_changed_at < $sixMonthsAgo) {
            $recentWarning = UserMessage::where('user_id', $userId)
                ->where('category', UserMessage::CAT_SECURITY)
                ->where('title', 'Time to update your password')
                ->where('created_at', '>', now()->subDays(30))
                ->exists();

            if (!$recentWarning) {
                $months = $user->password_changed_at->diffInMonths(now());

                UserMessage::send(
                    $userId,
                    UserMessage::CAT_SECURITY,
                    'Time to update your password',
                    "Your password hasn't been changed in {$months} months. "
                    . "For your security, we recommend updating it regularly. "
                    . "You can change your password from your account settings.",
                    route('account.security'),
                    'Change Password'
                );
            }
        }
    }
}
