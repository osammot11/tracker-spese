<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $selectedYear = (int) $request->input('year', Carbon::now()->year);

        // Calculate all 12 months for the selected year
        $monthlyData = [];
        $totalYearIncome = 0;
        $totalYearExpense = 0;

        for ($m = 1; $m <= 12; $m++) {
            $monthDate = Carbon::createFromDate($selectedYear, $m, 1);
            $inc = (float) Transaction::incomes()->forMonth($selectedYear, $m)->sum('amount');
            $exp = (float) Transaction::expenses()->forMonth($selectedYear, $m)->sum('amount');
            $bal = $inc - $exp;
            $savingsRate = $inc > 0 ? max(0, round(($bal / $inc) * 100, 1)) : 0;

            $totalYearIncome += $inc;
            $totalYearExpense += $exp;

            $monthlyData[] = [
                'month_num' => $m,
                'month_name' => ucfirst($monthDate->locale('it')->translatedFormat('F')),
                'income' => $inc,
                'expense' => $exp,
                'balance' => $bal,
                'savings_rate' => $savingsRate,
            ];
        }

        $totalYearBalance = $totalYearIncome - $totalYearExpense;
        $yearSavingsRate = $totalYearIncome > 0 ? max(0, round(($totalYearBalance / $totalYearIncome) * 100, 1)) : 0;

        // Category breakdown for the year
        $yearTransactions = Transaction::with('category')
            ->whereYear('date', $selectedYear)
            ->where('type', 'expense')
            ->get();

        $categoryYearBreakdown = $yearTransactions->groupBy('category_id')->map(function ($items) use ($totalYearExpense) {
            $cat = $items->first()->category;
            $amt = (float) $items->sum('amount');
            $pct = $totalYearExpense > 0 ? round(($amt / $totalYearExpense) * 100, 1) : 0;

            return [
                'name' => $cat ? $cat->name : 'Senza Categoria',
                'icon' => $cat ? $cat->icon : '📁',
                'color' => $cat ? $cat->color : '#6366f1',
                'amount' => $amt,
                'percentage' => $pct,
            ];
        })->sortByDesc('amount')->values();

        // Available years in transactions for filter dropdown
        $availableYears = Transaction::selectRaw('strftime("%Y", date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [Carbon::now()->year];
        }

        return view('reports.index', compact(
            'selectedYear',
            'monthlyData',
            'totalYearIncome',
            'totalYearExpense',
            'totalYearBalance',
            'yearSavingsRate',
            'categoryYearBreakdown',
            'availableYears'
        ));
    }

    public function exportCsv(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $query = Transaction::with(['category', 'subcategory'])->orderBy('date', 'desc');

        if ($year && $year !== 'all') {
            $query->whereYear('date', (int) $year);
        }
        if ($month && $month !== 'all') {
            $query->whereMonth('date', (int) $month);
        }

        $transactions = $query->get();

        $filename = 'transazioni_' . ($year ?: 'tutte') . ($month ? '_' . $month : '') . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'ID',
                'Data',
                'Tipo',
                'Categoria',
                'Sottocategoria',
                'Importo (€)',
                'Descrizione',
                'Metodo di Pagamento',
                'Note',
            ], ';');

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->date ? $t->date->format('d/m/Y') : '',
                    $t->type === 'expense' ? 'Spesa' : 'Entrata',
                    $t->category ? $t->category->name : '',
                    $t->subcategory ? $t->subcategory->name : '',
                    number_format($t->amount, 2, ',', ''),
                    $t->description ?? '',
                    $t->payment_method ?? '',
                    $t->notes ?? '',
                ], ';');
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
