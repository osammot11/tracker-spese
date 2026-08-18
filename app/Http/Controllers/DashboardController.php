<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedYear = (int) $request->input('year', Carbon::now()->year);
        $selectedMonth = (int) $request->input('month', Carbon::now()->month);

        $currentDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
        $monthName = $currentDate->locale('it')->translatedFormat('F Y');

        // Month Transactions
        $monthTransactions = Transaction::with(['category', 'subcategory'])
            ->forMonth($selectedYear, $selectedMonth)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalIncome = (float) $monthTransactions->where('type', 'income')->sum('amount');
        $totalExpense = (float) $monthTransactions->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? max(0, round(($netBalance / $totalIncome) * 100, 1)) : 0;

        // Expenses breakdown by category
        $expensesByCategory = $monthTransactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items) use ($totalExpense) {
                $category = $items->first()->category;
                $amount = (float) $items->sum('amount');
                $percentage = $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0;

                return [
                    'id' => $category ? $category->id : 0,
                    'name' => $category ? $category->name : 'Senza Categoria',
                    'icon' => $category ? $category->icon : '📁',
                    'color' => $category ? $category->color : '#94a3b8',
                    'amount' => $amount,
                    'percentage' => $percentage,
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        // Income breakdown by category
        $incomesByCategory = $monthTransactions->where('type', 'income')
            ->groupBy('category_id')
            ->map(function ($items) use ($totalIncome) {
                $category = $items->first()->category;
                $amount = (float) $items->sum('amount');
                $percentage = $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0;

                return [
                    'id' => $category ? $category->id : 0,
                    'name' => $category ? $category->name : 'Senza Categoria',
                    'icon' => $category ? $category->icon : '📁',
                    'color' => $category ? $category->color : '#10b981',
                    'amount' => $amount,
                    'percentage' => $percentage,
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        // Last 6 months trend
        $sixMonthsTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthObj = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->subMonths($i);
            $y = $monthObj->year;
            $m = $monthObj->month;

            $inc = (float) Transaction::incomes()->forMonth($y, $m)->sum('amount');
            $exp = (float) Transaction::expenses()->forMonth($y, $m)->sum('amount');

            $sixMonthsTrend[] = [
                'label' => ucfirst($monthObj->locale('it')->translatedFormat('M Y')),
                'income' => $inc,
                'expense' => $exp,
                'balance' => $inc - $exp,
            ];
        }

        // Recent transactions
        $recentTransactions = $monthTransactions->take(7);

        // All categories with subcategories for quick action modal
        $categories = Category::with('subcategories')->orderBy('name')->get();

        return view('dashboard', compact(
            'selectedYear',
            'selectedMonth',
            'monthName',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'savingsRate',
            'expensesByCategory',
            'incomesByCategory',
            'sixMonthsTrend',
            'recentTransactions',
            'categories'
        ));
    }
}
