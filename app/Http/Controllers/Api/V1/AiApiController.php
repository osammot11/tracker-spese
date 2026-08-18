<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AiApiController extends Controller
{
    public function overview(Request $request)
    {
        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);

        $monthTransactions = Transaction::with(['category', 'subcategory'])
            ->forMonth($year, $month)
            ->get();

        $totalIncome = (float) $monthTransactions->where('type', 'income')->sum('amount');
        $totalExpense = (float) $monthTransactions->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? max(0, round(($netBalance / $totalIncome) * 100, 1)) : 0;

        $expensesByCategory = $monthTransactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items) use ($totalExpense) {
                $cat = $items->first()->category;
                $amt = (float) $items->sum('amount');
                return [
                    'category_id' => $cat ? $cat->id : null,
                    'category_name' => $cat ? $cat->name : 'Senza Categoria',
                    'icon' => $cat ? $cat->icon : '📁',
                    'total_amount' => $amt,
                    'percentage' => $totalExpense > 0 ? round(($amt / $totalExpense) * 100, 1) : 0,
                    'count' => $items->count(),
                ];
            })->sortByDesc('total_amount')->values();

        $incomesByCategory = $monthTransactions->where('type', 'income')
            ->groupBy('category_id')
            ->map(function ($items) use ($totalIncome) {
                $cat = $items->first()->category;
                $amt = (float) $items->sum('amount');
                return [
                    'category_id' => $cat ? $cat->id : null,
                    'category_name' => $cat ? $cat->name : 'Senza Categoria',
                    'icon' => $cat ? $cat->icon : '📁',
                    'total_amount' => $amt,
                    'percentage' => $totalIncome > 0 ? round(($amt / $totalIncome) * 100, 1) : 0,
                    'count' => $items->count(),
                ];
            })->sortByDesc('total_amount')->values();

        return response()->json([
            'period' => [
                'year' => $year,
                'month' => $month,
                'formatted' => Carbon::createFromDate($year, $month, 1)->locale('it')->translatedFormat('F Y'),
            ],
            'totals' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance,
                'savings_rate_percent' => $savingsRate,
                'transactions_count' => $monthTransactions->count(),
            ],
            'expenses_by_category' => $expensesByCategory,
            'incomes_by_category' => $incomesByCategory,
        ]);
    }

    public function listTransactions(Request $request)
    {
        $query = Transaction::with(['category', 'subcategory']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('category_name')) {
            $catName = $request->category_name;
            $query->whereHas('category', function ($q) use ($catName) {
                $q->where('name', 'like', "%{$catName}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            if ($request->filled('month')) {
                $query->whereMonth('date', (int) $request->month);
            }
            if ($request->filled('year')) {
                $query->whereYear('date', (int) $request->year);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('subcategory', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $limit = min((int) $request->input('limit', 20), 100);
        $transactions = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->limit($limit)->get();

        return response()->json([
            'count' => $transactions->count(),
            'transactions' => $transactions->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => (float) $t->amount,
                    'date' => $t->date->format('Y-m-d'),
                    'category' => $t->category ? $t->category->name : null,
                    'subcategory' => $t->subcategory ? $t->subcategory->name : null,
                    'description' => $t->description,
                    'payment_method' => $t->payment_method,
                    'notes' => $t->notes,
                ];
            }),
        ]);
    }

    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:categories,id',
            'category_name' => 'nullable|string',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'subcategory_name' => 'nullable|string',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $type = $validated['type'];
        $categoryId = $validated['category_id'] ?? null;
        $subcategoryId = $validated['subcategory_id'] ?? null;

        // Smart Category Resolution if category_name was passed
        if (!$categoryId && !empty($validated['category_name'])) {
            $catName = trim($validated['category_name']);
            $foundCat = Category::where('name', 'like', "%{$catName}%")
                ->whereIn('type', [$type, 'both'])
                ->first();

            if ($foundCat) {
                $categoryId = $foundCat->id;
            } else {
                // Also check if category_name matches a subcategory
                $foundSub = Subcategory::where('name', 'like', "%{$catName}%")->first();
                if ($foundSub) {
                    $categoryId = $foundSub->category_id;
                    $subcategoryId = $foundSub->id;
                }
            }
        }

        // Smart Subcategory Resolution if subcategory_name was passed
        if (!$subcategoryId && !empty($validated['subcategory_name'])) {
            $subName = trim($validated['subcategory_name']);
            $subQuery = Subcategory::where('name', 'like', "%{$subName}%");
            if ($categoryId) {
                $subQuery->where('category_id', $categoryId);
            }
            $foundSub = $subQuery->first();
            if ($foundSub) {
                $subcategoryId = $foundSub->id;
                if (!$categoryId) {
                    $categoryId = $foundSub->category_id;
                }
            }
        }

        // Fallback default category if not found
        if (!$categoryId) {
            $defaultCat = Category::whereIn('type', [$type, 'both'])->first();
            $categoryId = $defaultCat ? $defaultCat->id : 1;
        }

        $date = !empty($validated['date']) ? $validated['date'] : Carbon::now()->format('Y-m-d');

        $transaction = Transaction::create([
            'type' => $type,
            'amount' => $validated['amount'],
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'date' => $date,
            'description' => $validated['description'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $transaction->load(['category', 'subcategory']);

        return response()->json([
            'success' => true,
            'message' => 'Transazione registrata con successo!',
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
                'date' => $transaction->date->format('Y-m-d'),
                'category' => $transaction->category ? $transaction->category->name : null,
                'subcategory' => $transaction->subcategory ? $transaction->subcategory->name : null,
                'description' => $transaction->description,
                'payment_method' => $transaction->payment_method,
            ],
        ], 201);
    }

    public function deleteTransaction(Request $request, Transaction $transaction)
    {
        $id = $transaction->id;
        $desc = $transaction->description ?: ($transaction->category ? $transaction->category->name : 'Transazione');
        $amount = (float) $transaction->amount;

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => "Transazione #{$id} ({$desc} da {$amount} €) eliminata con successo.",
        ]);
    }

    public function listCategories(Request $request)
    {
        $categories = Category::with('subcategories')->orderBy('type')->orderBy('name')->get();

        return response()->json([
            'categories' => $categories->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'type' => $c->type,
                    'icon' => $c->icon,
                    'color' => $c->color,
                    'subcategories' => $c->subcategories->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'name' => $s->name,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function createCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:expense,income,both',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?? '📁',
            'color' => $validated['color'] ?? '#6366f1',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Categoria '{$category->name}' creata con successo.",
            'category' => $category->load('subcategories'),
        ], 201);
    }

    public function createSubcategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $sub = $category->subcategories()->create($validated);

        return response()->json([
            'success' => true,
            'message' => "Sottocategoria '{$sub->name}' aggiunta alla categoria '{$category->name}'.",
            'subcategory' => $sub,
        ], 201);
    }
}
