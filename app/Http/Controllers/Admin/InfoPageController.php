<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfoPage;
use Illuminate\Http\Request;

class InfoPageController extends Controller
{
    public function index()
    {
        $pages = InfoPage::orderBy('sort_order')->get();
        return view('admin.info-pages.index', compact('pages'));
    }

    public function edit($id)
    {
        $page = InfoPage::findOrFail($id);
        return view('admin.info-pages.edit', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'intro' => 'nullable|string',
            'sections' => 'required|array',
            'sections.*.question' => 'required|string',
            'sections.*.answer' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $page = InfoPage::findOrFail($id);
        
        $page->update([
            'title' => $request->title,
            'intro' => $request->intro,
            'sections' => $request->sections,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'is_active' => $request->boolean('is_active', true)
        ]);

        return redirect()->route('admin.info-pages.index')
            ->with('success', 'Page updated successfully.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'pages' => 'required|array',
            'pages.*.id' => 'required|exists:info_pages,page_id',
            'pages.*.sort_order' => 'required|integer'
        ]);

        foreach ($request->pages as $item) {
            InfoPage::where('page_id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}