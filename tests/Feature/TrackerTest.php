<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Finanze');
        $response->assertSee('Entrate del Mese');
        $response->assertSee('Spese del Mese');
    }

    public function test_transactions_page_loads_successfully(): void
    {
        $response = $this->get('/transactions');
        $response->assertStatus(200);
        $response->assertSee('Gestione Transazioni');
    }

    public function test_api_transactions_list(): void
    {
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'transactions' => [
                'data',
                'current_page',
                'total'
            ],
            'summary' => [
                'total_income',
                'total_expense',
                'net_balance',
                'total_count'
            ]
        ]);
    }

    public function test_can_create_expense_transaction(): void
    {
        $category = Category::where('type', 'expense')->first();
        $subcategory = $category->subcategories()->first();

        $payload = [
            'type' => 'expense',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory ? $subcategory->id : null,
            'amount' => 45.50,
            'date' => '2026-08-18',
            'description' => 'Cena test',
            'payment_method' => 'Carta di Debito',
            'notes' => 'Note di test'
        ];

        $response = $this->postJson('/api/transactions', $payload);
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseHas('transactions', [
            'description' => 'Cena test',
            'amount' => 45.50
        ]);
    }

    public function test_can_update_transaction(): void
    {
        $transaction = Transaction::first();

        $response = $this->putJson('/api/transactions/' . $transaction->id, [
            'type' => $transaction->type,
            'category_id' => $transaction->category_id,
            'subcategory_id' => $transaction->subcategory_id,
            'amount' => 99.99,
            'date' => '2026-08-18',
            'description' => 'Descrizione aggiornata',
            'payment_method' => 'Bonifico Bancario'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 99.99,
            'description' => 'Descrizione aggiornata'
        ]);
    }

    public function test_can_delete_transaction(): void
    {
        $transaction = Transaction::first();

        $response = $this->deleteJson('/api/transactions/' . $transaction->id);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id
        ]);
    }

    public function test_categories_management(): void
    {
        $response = $this->get('/categories');
        $response->assertStatus(200);
        $response->assertSee('Categorie');

        // API create category
        $catResponse = $this->postJson('/api/categories', [
            'name' => 'Nuova Categoria Test',
            'type' => 'expense',
            'icon' => '⭐',
            'color' => '#f59e0b'
        ]);
        $catResponse->assertStatus(201);
        $newCatId = $catResponse->json('category.id');

        // API create subcategory
        $subResponse = $this->postJson('/api/categories/' . $newCatId . '/subcategories', [
            'name' => 'Nuova SottoTest'
        ]);
        $subResponse->assertStatus(201);

        $this->assertDatabaseHas('subcategories', [
            'category_id' => $newCatId,
            'name' => 'Nuova SottoTest'
        ]);
    }

    public function test_reports_page_and_export(): void
    {
        $response = $this->get('/reports?year=2026');
        $response->assertStatus(200);
        $response->assertSee('Analisi Annuale');

        // Test CSV export
        $csvResponse = $this->get('/reports/export-csv?year=2026');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
