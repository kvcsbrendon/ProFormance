<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerContact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerContact::orderByDesc('query_id');
        if ($request->filled('status'))  { $query->where('contact_status', $request->status); }
        if ($request->filled('subject')) { $query->where('subject_select', $request->subject); }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email_address', 'like', "%{$s}%")
                  ->orWhere('message_description', 'like', "%{$s}%");
            });
        }
        $contacts = $query->paginate(20)->withQueryString();
        return view('admin.contacts.index', compact('contacts'));
    }

    public function show($queryId)
    {
        $contact = CustomerContact::where('query_id', $queryId)->firstOrFail();
        return view('admin.contacts.show', compact('contact'));
    }

    public function updateStatus(Request $request, $queryId)
    {
        $request->validate(['contact_status' => 'required|in:Pending,Solved']);
        $contact = CustomerContact::where('query_id', $queryId)->firstOrFail();
        $contact->contact_status = $request->contact_status;
        $contact->save();
        return back()->with('success', 'Ticket status updated.');
    }
}
