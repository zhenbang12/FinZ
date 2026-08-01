<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|in:expense,income',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['type'] = $validated['type'] ?? 'expense';
        $validated['color'] = $validated['color'] ?: '#6366f1';

        $category = Category::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'category' => $category]);
        }

        return redirect()->back()->with('success', "Category '{$category->name}' created successfully.");
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== null && $category->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized category action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|in:expense,income',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->user_id !== null && $category->user_id !== $request->user()->id) {
            abort(403, 'System categories cannot be deleted.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
