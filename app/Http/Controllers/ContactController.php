<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerContactRequest;
use App\Models\CustomerContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        $subjects = [
            CustomerContact::SUBJECT_GENERAL,
            CustomerContact::SUBJECT_SUPPORT,
            CustomerContact::SUBJECT_FEEDBACK,
        ];

        return view('contact', compact('subjects'));
    }
    public function store(StoreCustomerContactRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['contact_status'] = CustomerContact::STATUS_PENDING;

        CustomerContact::create($data);

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you for getting in touch. We will respond as soon as we can.');
    }
}
