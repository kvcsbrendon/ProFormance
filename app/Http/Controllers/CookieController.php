<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CookieController extends Controller
{
    public function accept()
    {
        return response()->json(['success' => true])
            ->cookie(
                'edu_notice_accepted',
                'true',
                60 * 24 * 30,
                '/',
                null,
                false, // secure (set true when HTTPS)
                true,
                false,
                'Lax'
            );
    }
}