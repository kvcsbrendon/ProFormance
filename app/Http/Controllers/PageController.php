<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function shipping() { return view('pages.shipping'); }
    public function returns() { return view('pages.returns'); }
    public function sizeGuide() { return view('pages.size-guide'); }
    public function faq() { return view('pages.faq'); }
    public function giftCards() { return view('pages.gift-cards'); }

    public function studentDiscount() { return view('pages.discounts.student'); }
    public function teacherDiscount() { return view('pages.discounts.teacher'); }
    public function firstResponderDiscount() { return view('pages.discounts.first-responder'); }

    public function sustainability() { return view('pages.sustainability'); }
    public function careers() { return view('pages.careers'); }
    public function affiliates() { return view('pages.affiliates'); }

    public function privacyPolicy() { return view('legal.privacy'); }
    public function gdpr() { return view('legal.gdpr'); }
    public function terms() { return view('legal.terms'); }
    public function cookies() { return view('legal.cookies'); }
}