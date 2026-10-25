<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TrackerMcpTools
{
    private function tool(string $name, string $description, array $properties, array $required = [], bool $readOnly = false, bool $destructive = false): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object) $properties,
                'required' => $required,
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint' => $readOnly,
                'destructiveHint' => $destructive,
                'openWorldHint' => false,
            ],
        ];
    }

    public function definitions(): array
    {
        $id = ['type' => 'integer', 'minimum' => 1];
        $date = ['type' => 'string', 'format' => 'date', 'description' => 'Data nel formato YYYY-MM-DD.'];
        $kind = ['type' => 'string', 'enum' => ['expense', 'income'], 'description' => 'expense = spesa; income = entrata.'];
        $amount = ['type' => 'number', 'exclusiveMinimum' => 0, 'description' => 'Importo positivo in euro.'];
        $transaction = [
            'type' => $kind,
            'amount' => $amount,
            'category_id' => $id + ['description' => 'ID ottenuto con list_categories.'],
            'subcategory_id' => $id + ['description' => 'ID di una sottocategoria della categoria scelta.'],
            'date' => $date,
            'description' => ['type' => 'string', 'maxLength' => 255],
            'payment_method' => ['type' => 'string', 'maxLength' => 100],
            'notes' => ['type' => 'string', 'maxLength' => 1000],
        ];
        $category = [
            'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100],
            'type' => ['type' => 'string', 'enum' => ['expense', 'income', 'both']],
            'icon' => ['type' => 'string', 'maxLength' => 50],
            'color' => ['type' => 'string', 'pattern' => '^#[0-9A-Fa-f]{6}$'],
        ];

        return [
            $this->tool('get_monthly_overview', 'Legge entrate, spese, saldo e ripartizione per categoria di un mese. Se mese e anno sono omessi usa il mese corrente in Europe/Rome.', ['year' => ['type' => 'integer', 'minimum' => 2000, 'maximum' => 2100], 'month' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 12]], readOnly: true),
            $this->tool('get_annual_report', 'Legge i totali mensili e annuali per un anno, utili per confronti e andamento.', ['year' => ['type' => 'integer', 'minimum' => 2000, 'maximum' => 2100]], readOnly: true),
            $this->tool('list_transactions', 'Cerca transazioni e restituisce ID, dettagli e totali del filtro. Usa gli ID prima di modificare o eliminare.', [
                'type' => $kind, 'category_id' => $id, 'subcategory_id' => $id,
                'date_from' => $date, 'date_to' => $date,
                'search' => ['type' => 'string', 'maxLength' => 100],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                'offset' => ['type' => 'integer', 'minimum' => 0],
            ], readOnly: true),
            $this->tool('list_categories', 'Elenca categorie e sottocategorie con i relativi ID e tipi. Usalo prima di registrare una transazione.', [], readOnly: true),
            $this->tool('create_transaction', 'Registra una spesa o entrata. Richiede categoria esistente: chiama prima list_categories. Se la sottocategoria manca, ometti subcategory_id.', $transaction, ['type', 'amount', 'category_id', 'date']),
            $this->tool('update_transaction', 'Modifica i campi indicati di una transazione identificata con list_transactions. Non cambia gli altri campi.', ['id' => $id] + $transaction, ['id'], destructive: true),
            $this->tool('delete_transaction', 'Elimina definitivamente una transazione identificata con list_transactions. Verifica ID e dettagli prima della chiamata.', ['id' => $id], ['id'], destructive: true),
            $this->tool('create_category', 'Crea una categoria di spese, entrate o entrambe.', $category, ['name', 'type']),
            $this->tool('update_category', 'Modifica una categoria esistente. I campi non indicati restano invariati.', ['id' => $id] + $category, ['id'], destructive: true),
            $this->tool('delete_category', 'Elimina definitivamente una categoria e tutte le sue transazioni per effetto della cascata del database. Richiede confirm_delete=true.', ['id' => $id, 'confirm_delete' => ['type' => 'boolean', 'description' => 'Imposta true solo dopo aver verificato la categoria e il numero di transazioni collegate.']], ['id', 'confirm_delete'], destructive: true),
            $this->tool('create_subcategory', 'Aggiunge una sottocategoria a una categoria esistente.', ['category_id' => $id, 'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100]], ['category_id', 'name']),
            $this->tool('delete_subcategory', 'Elimina una sottocategoria; le transazioni collegate restano, senza sottocategoria.', ['id' => $id], ['id'], destructive: true),
        ];
    }

    private function validated(array $input, array $rules): array
    {
        return Validator::make($input, $rules)->validate();
    }

    private function transactionData(array $input, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';
        return $this->validated($input, [
            'type' => "$required|in:expense,income",
            'amount' => "$required|numeric|min:0.01|max:99999999.99",
            'category_id' => "$required|integer|exists:categories,id",
            'subcategory_id' => 'sometimes|nullable|integer|exists:subcategories,id',
            'date' => "$required|date_format:Y-m-d",
            'description' => 'sometimes|nullable|string|max:255',
            'payment_method' => 'sometimes|nullable|string|max:100',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);
    }

    private function checkTransactionCategory(array $data, ?Transaction $existing = null): void
    {
        $categoryId = $data['category_id'] ?? $existing?->category_id;
        $type = $data['type'] ?? $existing?->type;
        $subcategoryId = array_key_exists('subcategory_id', $data) ? $data['subcategory_id'] : $existing?->subcategory_id;
        $category = Category::findOrFail($categoryId);
        if (!in_array($category->type, [$type, 'both'], true)) {
            throw ValidationException::withMessages(['category_id' => 'La categoria non è compatibile con il tipo della transazione.']);
        }
        if (array_key_exists('category_id', $data) && !array_key_exists('subcategory_id', $data) && $existing && $existing->category_id !== $categoryId) {
            $subcategoryId = null;
        }
        if ($subcategoryId && !Subcategory::whereKey($subcategoryId)->where('category_id', $categoryId)->exists()) {
            throw ValidationException::withMessages(['subcategory_id' => 'La sottocategoria non appartiene alla categoria scelta.']);
        }
    }

    private function transaction(Transaction $transaction): array
    {
        $transaction->load(['category', 'subcategory']);
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'date' => $transaction->date->format('Y-m-d'),
            'category_id' => $transaction->category_id,
            'category' => $transaction->category?->name,
            'subcategory_id' => $transaction->subcategory_id,
            'subcategory' => $transaction->subcategory?->name,
            'description' => $transaction->description,
            'payment_method' => $transaction->payment_method,
            'notes' => $transaction->notes,
        ];
    }

    public function call(string $name, array $input): array
    {
        $definition = collect($this->definitions())->firstWhere('name', $name);
        if (!$definition) {
            throw new \InvalidArgumentException("Strumento sconosciuto: {$name}");
        }
        $unknown = array_diff(array_keys($input), array_keys((array) $definition['inputSchema']['properties']));
        if ($unknown) {
            throw ValidationException::withMessages(['arguments' => ['Parametri sconosciuti: '.implode(', ', $unknown)]]);
        }
        return match ($name) {
            'get_monthly_overview' => $this->monthlyOverview($input),
            'get_annual_report' => $this->annualReport($input),
            'list_transactions' => $this->listTransactions($input),
            'list_categories' => $this->listCategories(),
            'create_transaction' => $this->createTransaction($input),
            'update_transaction' => $this->updateTransaction($input),
            'delete_transaction' => $this->deleteTransaction($input),
            'create_category' => $this->createCategory($input),
            'update_category' => $this->updateCategory($input),
            'delete_category' => $this->deleteCategory($input),
            'create_subcategory' => $this->createSubcategory($input),
            'delete_subcategory' => $this->deleteSubcategory($input),
        };
    }

    private function monthlyOverview(array $input): array
    {
        $data = $this->validated($input, ['year' => 'sometimes|integer|between:2000,2100', 'month' => 'sometimes|integer|between:1,12']);
        $now = Carbon::now('Europe/Rome');
        $year = $data['year'] ?? $now->year;
        $month = $data['month'] ?? $now->month;
        $rows = Transaction::with('category')->forMonth($year, $month)->get();
        $income = (float) $rows->where('type', 'income')->sum('amount');
        $expense = (float) $rows->where('type', 'expense')->sum('amount');
        $groups = fn ($type) => $rows->where('type', $type)->groupBy('category_id')->map(fn ($items) => [
            'category_id' => $items->first()->category_id,
            'category' => $items->first()->category?->name,
            'total' => round((float) $items->sum('amount'), 2),
            'count' => $items->count(),
        ])->values()->all();
        return [
            'year' => $year, 'month' => $month,
            'income' => round($income, 2), 'expense' => round($expense, 2),
            'balance' => round($income - $expense, 2),
            'savings_rate_percent' => $income > 0 ? round(($income - $expense) / $income * 100, 1) : null,
            'transaction_count' => $rows->count(),
            'expenses_by_category' => $groups('expense'),
            'incomes_by_category' => $groups('income'),
        ];
    }

    private function annualReport(array $input): array
    {
        $data = $this->validated($input, ['year' => 'sometimes|integer|between:2000,2100']);
        $year = $data['year'] ?? Carbon::now('Europe/Rome')->year;
        $rows = Transaction::whereYear('date', $year)->get();
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $items = $rows->filter(fn ($row) => (int) $row->date->format('n') === $month);
            $income = (float) $items->where('type', 'income')->sum('amount');
            $expense = (float) $items->where('type', 'expense')->sum('amount');
            $months[] = ['month' => $month, 'income' => round($income, 2), 'expense' => round($expense, 2), 'balance' => round($income - $expense, 2)];
        }
        $income = (float) $rows->where('type', 'income')->sum('amount');
        $expense = (float) $rows->where('type', 'expense')->sum('amount');
        return ['year' => $year, 'income' => round($income, 2), 'expense' => round($expense, 2), 'balance' => round($income - $expense, 2), 'months' => $months];
    }

    private function listTransactions(array $input): array
    {
        $data = $this->validated($input, [
            'type' => 'sometimes|in:expense,income', 'category_id' => 'sometimes|integer|min:1',
            'subcategory_id' => 'sometimes|integer|min:1', 'date_from' => 'sometimes|date_format:Y-m-d',
            'date_to' => 'sometimes|date_format:Y-m-d', 'search' => 'sometimes|string|max:100',
            'limit' => 'sometimes|integer|between:1,100', 'offset' => 'sometimes|integer|min:0',
        ]);
        $query = Transaction::query();
        foreach (['type', 'category_id', 'subcategory_id'] as $field) {
            if (isset($data[$field])) $query->where($field, $data[$field]);
        }
        if (isset($data['date_from'])) $query->whereDate('date', '>=', $data['date_from']);
        if (isset($data['date_to'])) $query->whereDate('date', '<=', $data['date_to']);
        if (isset($data['search'])) {
            $term = str_replace(['%', '_'], ['\\%', '\\_'], $data['search']);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('payment_method', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('subcategory', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }
        $totals = (clone $query)->selectRaw("COUNT(*) as count, SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")->first();
        $items = $query->orderByDesc('date')->orderByDesc('id')->offset($data['offset'] ?? 0)->limit($data['limit'] ?? 20)->get();
        return [
            'total_count' => (int) $totals->count,
            'total_income' => round((float) $totals->income, 2),
            'total_expense' => round((float) $totals->expense, 2),
            'offset' => $data['offset'] ?? 0,
            'transactions' => $items->map(fn ($item) => $this->transaction($item))->all(),
        ];
    }

    private function listCategories(): array
    {
        return ['categories' => Category::with('subcategories')->withCount('transactions')->orderBy('type')->orderBy('name')->get()->map(fn ($category) => [
            'id' => $category->id, 'name' => $category->name, 'type' => $category->type,
            'icon' => $category->icon, 'color' => $category->color,
            'transaction_count' => $category->transactions_count,
            'subcategories' => $category->subcategories->map(fn ($sub) => ['id' => $sub->id, 'name' => $sub->name])->all(),
        ])->all()];
    }

    private function createTransaction(array $input): array
    {
        $data = $this->transactionData($input, true);
        $this->checkTransactionCategory($data);
        return ['transaction' => $this->transaction(Transaction::create($data))];
    }

    private function updateTransaction(array $input): array
    {
        $id = $this->validated($input, ['id' => 'required|integer|min:1'])['id'];
        $transaction = Transaction::findOrFail($id);
        $data = $this->transactionData($input, false);
        $this->checkTransactionCategory($data, $transaction);
        if (isset($data['category_id']) && $data['category_id'] !== $transaction->category_id && !array_key_exists('subcategory_id', $data)) {
            $data['subcategory_id'] = null;
        }
        $transaction->update($data);
        return ['transaction' => $this->transaction($transaction)];
    }

    private function deleteTransaction(array $input): array
    {
        $id = $this->validated($input, ['id' => 'required|integer|min:1'])['id'];
        $transaction = Transaction::findOrFail($id);
        $snapshot = $this->transaction($transaction);
        $transaction->delete();
        return ['deleted_transaction' => $snapshot];
    }

    private function categoryData(array $input, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';
        return $this->validated($input, [
            'name' => "$required|string|max:100",
            'type' => "$required|in:expense,income,both",
            'icon' => 'sometimes|nullable|string|max:50',
            'color' => 'sometimes|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);
    }

    private function createCategory(array $input): array
    {
        $data = $this->categoryData($input, true);
        $category = Category::create($data + ['icon' => '📁', 'color' => '#6366f1']);
        return ['category' => $category->toArray()];
    }

    private function updateCategory(array $input): array
    {
        $id = $this->validated($input, ['id' => 'required|integer|min:1'])['id'];
        $category = Category::findOrFail($id);
        $data = $this->categoryData($input, false);
        if (isset($data['type']) && $data['type'] !== $category->type && $data['type'] !== 'both') {
            $incompatible = $category->transactions()->where('type', '!=', $data['type'])->count();
            if ($incompatible > 0) throw ValidationException::withMessages(['type' => "La categoria ha {$incompatible} transazioni incompatibili col nuovo tipo."]);
        }
        $category->update($data);
        return ['category' => $category->toArray()];
    }

    private function deleteCategory(array $input): array
    {
        $data = $this->validated($input, ['id' => 'required|integer|min:1', 'confirm_delete' => 'required|accepted']);
        $category = Category::findOrFail($data['id']);
        return DB::transaction(function () use ($category) {
            $result = ['deleted_category' => ['id' => $category->id, 'name' => $category->name], 'deleted_transactions' => $category->transactions()->count()];
            $category->delete();
            return $result;
        });
    }

    private function createSubcategory(array $input): array
    {
        $data = $this->validated($input, ['category_id' => 'required|integer|exists:categories,id', 'name' => 'required|string|max:100']);
        $subcategory = Category::findOrFail($data['category_id'])->subcategories()->create(['name' => $data['name']]);
        return ['subcategory' => $subcategory->toArray()];
    }

    private function deleteSubcategory(array $input): array
    {
        $id = $this->validated($input, ['id' => 'required|integer|min:1'])['id'];
        $subcategory = Subcategory::findOrFail($id);
        $result = ['deleted_subcategory' => ['id' => $subcategory->id, 'name' => $subcategory->name], 'affected_transactions' => $subcategory->transactions()->count()];
        $subcategory->delete();
        return $result;
    }
}
