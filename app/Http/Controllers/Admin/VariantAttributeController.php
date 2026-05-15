<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariantAttribute;
use App\Models\VariantOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VariantAttributeController extends Controller
{
    /**
     * List all attributes with their options.
     */
    public function index()
    {
        $attributes = VariantAttribute::with(['options' => function ($q) {
            $q->orderBy('sort_order');
        }])
        ->orderBy('selection_order')
        ->get();

        return view('admin.variant-attributes.index', compact('attributes'));
    }

    /**
     * Create a new attribute (e.g. "Material").
     */
    public function storeAttribute(Request $request)
    {
        $data = $request->validate([
            'attribute_name' => 'required|string|max:50|unique:variant_attributes,attribute_name',
            'display_name'   => 'required|string|max:100',
        ]);

        $maxOrder = VariantAttribute::max('selection_order') ?? 0;

        VariantAttribute::create([
            'attribute_name'  => strtolower(str_replace(' ', '_', $data['attribute_name'])),
            'display_name'    => $data['display_name'],
            'selection_order' => $maxOrder + 1,
            'is_active'       => true,
        ]);

        return back()->with('success', "Attribute \"{$data['display_name']}\" created.");
    }

    /**
     * Update an attribute.
     */
    public function updateAttribute(Request $request, $attributeId)
    {
        $attr = VariantAttribute::where('attribute_id', $attributeId)->firstOrFail();

        $data = $request->validate([
            'display_name'    => 'required|string|max:100',
            'selection_order' => 'required|integer|min:0',
            'is_active'       => 'sometimes|in:1',
        ]);

        $attr->update([
            'display_name'    => $data['display_name'],
            'selection_order' => $data['selection_order'],
            'is_active'       => $request->has('is_active'),
        ]);

        return back()->with('success', "Attribute updated.");
    }

    /**
     * Delete an attribute (only if no options are in use).
     */
    public function destroyAttribute($attributeId)
    {
        $attr = VariantAttribute::where('attribute_id', $attributeId)->firstOrFail();

        // Check if any options are linked to variants
        $inUse = DB::table('variant_combinations')
            ->join('variant_options', 'variant_combinations.option_id', '=', 'variant_options.option_id')
            ->where('variant_options.attribute_id', $attributeId)
            ->exists();

        if ($inUse) {
            return back()->withErrors(['attribute' => "Cannot delete \"{$attr->display_name}\" — its options are assigned to variants."]);
        }

        // Delete options first, then attribute
        VariantOption::where('attribute_id', $attributeId)->delete();
        $attr->delete();

        return back()->with('success', "Attribute \"{$attr->display_name}\" and all its options deleted.");
    }

    /**
     * Add a new option to an attribute (e.g. Size → "XXL").
     */
    public function storeOption(Request $request, $attributeId)
    {
        $attr = VariantAttribute::where('attribute_id', $attributeId)->firstOrFail();

        $data = $request->validate([
            'display_value' => 'required|string|max:100',
            'variant_value' => 'nullable|string|max:100',
        ]);

        $maxSort = VariantOption::where('attribute_id', $attributeId)->max('sort_order') ?? 0;

        VariantOption::create([
            'attribute_id'  => $attributeId,
            'variant_value' => $data['variant_value'] ?? strtolower(str_replace(' ', '_', $data['display_value'])),
            'display_value' => $data['display_value'],
            'sort_order'    => $maxSort + 1,
            'is_active'     => true,
        ]);

        return back()->with('success', "Option \"{$data['display_value']}\" added to {$attr->display_name}.");
    }

    /**
     * Update an option.
     */
    public function updateOption(Request $request, $optionId)
    {
        $option = VariantOption::where('option_id', $optionId)->firstOrFail();

        $data = $request->validate([
            'display_value' => 'required|string|max:100',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'sometimes|in:1',
        ]);

        $option->update([
            'display_value' => $data['display_value'],
            'sort_order'    => $data['sort_order'] ?? $option->sort_order,
            'is_active'     => $request->has('is_active'),
        ]);

        return back()->with('success', "Option updated.");
    }

    /**
     * Delete an option (only if not assigned to any variant).
     */
    public function destroyOption($optionId)
    {
        $option = VariantOption::where('option_id', $optionId)->firstOrFail();

        $inUse = DB::table('variant_combinations')
            ->where('option_id', $optionId)
            ->exists();

        if ($inUse) {
            return back()->withErrors(['option' => "Cannot delete \"{$option->display_value}\" — it's assigned to variants. Remove it from variants first."]);
        }

        $option->delete();
        return back()->with('success', "Option \"{$option->display_value}\" deleted.");
    }
}