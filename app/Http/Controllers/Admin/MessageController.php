<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserMessage;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function create()
    {
        $customers = User::where('user_role', '!=', 'admin')
            ->with('loginDetail')
            ->orderBy('first_name')
            ->get();

        return view('admin.messages.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient'  => 'required|in:single,all',
            'user_id'    => 'required_if:recipient,single|nullable|exists:users,user_id',
            'category'   => 'required|in:order,security,promotional,system',
            'title'      => 'required|string|max:200',
            'body'       => 'required|string|max:2000',
            'link_url'   => 'nullable|string|max:500',
            'link_label' => 'nullable|string|max:100',
        ]);

        if ($data['recipient'] === 'single') {
            UserMessage::send(
                $data['user_id'],
                $data['category'],
                $data['title'],
                $data['body'],
                $data['link_url'] ?? null,
                $data['link_label'] ?? null
            );
            $count = 1;
        } else {
            $users = User::where('user_role', '!=', 'admin')->where('is_active', true)->pluck('user_id');
            foreach ($users as $uid) {
                UserMessage::send(
                    $uid,
                    $data['category'],
                    $data['title'],
                    $data['body'],
                    $data['link_url'] ?? null,
                    $data['link_label'] ?? null
                );
            }
            $count = $users->count();
        }

        return redirect()->route('admin.messages.create')
            ->with('success', "Message sent to {$count} user(s).");
    }
}
