<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\TrackerMcpTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrackerMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_lifecycle_and_filtered_totals(): void
    {
        $tools = app(TrackerMcpTools::class);
        $category = Category::create(['name' => 'Caffè', 'type' => 'expense', 'icon' => '☕', 'color' => '#123456']);
        $other = Category::create(['name' => 'Altro', 'type' => 'expense', 'icon' => '📁', 'color' => '#654321']);
        $subcategory = $tools->call('create_subcategory', ['category_id' => $category->id, 'name' => 'Bar'])['subcategory'];

        $created = $tools->call('create_transaction', [
            'type' => 'expense', 'amount' => 3.50, 'category_id' => $category->id,
            'subcategory_id' => $subcategory['id'], 'date' => '2026-10-25', 'description' => 'Espresso',
        ])['transaction'];
        $this->assertSame('Bar', $created['subcategory']);

        $listed = $tools->call('list_transactions', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31']);
        $this->assertSame(1, $listed['total_count']);
        $this->assertSame(3.50, $listed['total_expense']);

        $updated = $tools->call('update_transaction', ['id' => $created['id'], 'amount' => 4.25, 'category_id' => $other->id])['transaction'];
        $this->assertSame(4.25, $updated['amount']);
        $this->assertNull($updated['subcategory_id']);

        $overview = $tools->call('get_monthly_overview', ['year' => 2026, 'month' => 10]);
        $this->assertSame(4.25, $overview['expense']);
        $annual = $tools->call('get_annual_report', ['year' => 2026]);
        $this->assertSame(4.25, $annual['expense']);

        $tools->call('delete_transaction', ['id' => $created['id']]);
        $this->assertDatabaseMissing('transactions', ['id' => $created['id']]);
    }

    public function test_invalid_subcategory_and_category_type_are_rejected(): void
    {
        $tools = app(TrackerMcpTools::class);
        $expense = Category::create(['name' => 'Spesa', 'type' => 'expense']);
        $income = Category::create(['name' => 'Stipendio', 'type' => 'income']);
        $sub = $tools->call('create_subcategory', ['category_id' => $income->id, 'name' => 'Bonus'])['subcategory'];

        foreach ([
            ['category_id' => $income->id],
            ['category_id' => $expense->id, 'subcategory_id' => $sub['id']],
        ] as $fields) {
            try {
                $tools->call('create_transaction', [
                    'type' => 'expense', 'amount' => 5, 'date' => '2026-10-25',
                ] + $fields);
                $this->fail('La categoria incompatibile doveva essere rifiutata.');
            } catch (ValidationException) {
                $this->assertSame(0, Transaction::count());
            }
        }
    }

    public function test_category_deletion_requires_explicit_flag_and_reports_cascade(): void
    {
        $tools = app(TrackerMcpTools::class);
        $category = Category::create(['name' => 'Viaggi', 'type' => 'expense']);
        $tools->call('create_transaction', ['type' => 'expense', 'amount' => 10, 'category_id' => $category->id, 'date' => '2026-10-25']);

        try {
            $tools->call('delete_category', ['id' => $category->id, 'confirm_delete' => false]);
            $this->fail('La conferma esplicita è obbligatoria.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('categories', ['id' => $category->id]);
        }

        $result = $tools->call('delete_category', ['id' => $category->id, 'confirm_delete' => true]);
        $this->assertSame(1, $result['deleted_transactions']);
        $this->assertSame(0, Transaction::count());
    }
}
