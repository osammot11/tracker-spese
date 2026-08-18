<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('subcategories')->orderBy('name')->get();

        return view('transactions.index', [
            'categories' => $categories,
            'initialFilters' => [
                'type' => $request->input('type', ''),
                'category_id' => $request->input('category_id', ''),
                'subcategory_id' => $request->input('subcategory_id', ''),
                'search' => $request->input('search', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'month' => $request->input('month', Carbon::now()->month),
                'year' => $request->input('year', Carbon::now()->year),
            ]
        ]);
    }

    public function apiList(Request $request)
    {
        $query = Transaction::with(['category', 'subcategory']);

        if ($request->filled('type') && in_array($request->type, ['expense', 'income'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // If date_from and date_to are not set, filter by month & year if provided
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            if ($request->filled('month') && $request->month !== 'all') {
                $query->whereMonth('date', (int) $request->month);
            }
            if ($request->filled('year') && $request->year !== 'all') {
                $query->whereYear('date', (int) $request->year);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('subcategory', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Compute summary for the current filtered dataset
        $allFiltered = (clone $query)->get();
        $totalIncome = (float) $allFiltered->where('type', 'income')->sum('amount');
        $totalExpense = (float) $allFiltered->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['date', 'amount', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('date', 'desc')->orderBy('id', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance,
                'total_count' => $allFiltered->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $transaction = Transaction::create($validated);
        $transaction->load(['category', 'subcategory']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transazione registrata con successo!',
                'transaction' => $transaction,
            ], 201);
        }

        return redirect()->back()->with('success', 'Transazione aggiunta con successo!');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'type' => 'required|in:expense,income',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $transaction->update($validated);
        $transaction->load(['category', 'subcategory']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transazione aggiornata con successo!',
                'transaction' => $transaction,
            ]);
        }

        return redirect()->back()->with('success', 'Transazione modificata con successo!');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $transaction->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transazione eliminata con successo!',
            ]);
        }

        return redirect()->back()->with('success', 'Transazione eliminata!');
    }
}
