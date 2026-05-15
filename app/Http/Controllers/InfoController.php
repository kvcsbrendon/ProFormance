<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InfoPage;

class InfoController extends Controller
{
    // Dynamic page loader
    public function show($slug)
{
    $page = InfoPage::where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    $title = $page->title;
    $intro = $page->intro;
    
    // Safely handle sections
    $sections = $page->sections ?? [];
    $items = [];
    
    foreach ($sections as $section) {
        $items[] = [
            'q' => $section['question'] ?? $section['q'] ?? '',
            'a' => $section['answer'] ?? $section['a'] ?? ''
        ];
    }

    return view('info-page', compact('title', 'intro', 'items'));
}

    // Keep these for backward compatibility or redirect them
    public function faq() { return redirect()->route('page.show', 'faq'); }
    public function terms() { return redirect()->route('page.show', 'terms'); }
    public function privacy() { return redirect()->route('page.show', 'privacy'); }
    public function gdpr() { return redirect()->route('page.show', 'gdpr'); }
    public function cookies() { return redirect()->route('page.show', 'cookies'); }
    public function shipping() { return redirect()->route('page.show', 'shipping'); }
    public function returns() { return redirect()->route('page.show', 'returns'); }
    public function sizeGuide() { return redirect()->route('page.show', 'size-guide'); }
    public function giftCards() { return redirect()->route('page.show', 'gift-cards'); }
    public function discounts() { return redirect()->route('page.show', 'discounts'); }
    public function about() { return redirect()->route('page.show', 'about'); }
    public function affiliates() { return redirect()->route('page.show', 'affiliates'); }
    public function careers() { return redirect()->route('page.show', 'careers'); }
    public function sustainability() { return redirect()->route('page.show', 'sustainability'); }
}