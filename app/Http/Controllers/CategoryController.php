<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['subcategories'])
            ->withCount('transactions')
            ->withSum('transactions', 'amount')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function apiCategories()
    {
        $categories = Category::with('subcategories')->orderBy('name')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:expense,income,both',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        $category = Category::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoria creata con successo!',
                'category' => $category->load('subcategories'),
            ], 201);
        }

        return redirect()->route('categories.index')->with('success', 'Categoria creata con successo!');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:expense,income,both',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        $category->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoria aggiornata con successo!',
                'category' => $category->load('subcategories'),
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Categoria aggiornata con successo!');
    }

    public function destroy(Request $request, Category $category)
    {
        $category->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoria eliminata con successo!',
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Categoria eliminata!');
    }

    public function storeSubcategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $subcategory = $category->subcategories()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sottocategoria creata con successo!',
                'subcategory' => $subcategory,
            ], 201);
        }

        return redirect()->route('categories.index')->with('success', 'Sottocategoria aggiunta!');
    }

    public function destroySubcategory(Request $request, Subcategory $subcategory)
    {
        $subcategory->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sottocategoria eliminata con successo!',
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Sottocategoria eliminata!');
    }
}
