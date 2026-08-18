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

    public function test_unauthorized_user_is_redirected_to_pin(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('pin.show'));
    }

    public function test_pin_page_loads_successfully(): void
    {
        $response = $this->get('/pin');
        $response->assertStatus(200);
        $response->assertSee('Accesso Protetto');
    }

    public function test_wrong_pin_fails(): void
    {
        $response = $this->post('/pin', ['pin' => '9999']);
        $response->assertSessionHasErrors(['pin']);
        $this->assertFalse(session('pin_verified', false));
    }

    public function test_correct_pin_authenticates_user(): void
    {
        $response = $this->post('/pin', ['pin' => '1234']);
        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(session('pin_verified', false));
    }

    public function test_custom_pin_from_env_config(): void
    {
        config(['app.pin' => '7890']);

        $wrong = $this->post('/pin', ['pin' => '1234']);
        $wrong->assertSessionHasErrors(['pin']);

        $correct = $this->post('/pin', ['pin' => '7890']);
        $correct->assertRedirect(route('dashboard'));
        $this->assertTrue(session('pin_verified', false));
    }

    public function test_dashboard_page_loads_with_pin(): void
    {
        $response = $this->withSession(['pin_verified' => true])->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Finanze');
        $response->assertSee('Entrate del Mese');
        $response->assertSee('Spese del Mese');
    }

    public function test_transactions_page_loads_successfully(): void
    {
        $response = $this->withSession(['pin_verified' => true])->get('/transactions');
        $response->assertStatus(200);
        $response->assertSee('Gestione Transazioni');
    }

    public function test_api_transactions_list(): void
    {
        $response = $this->withSession(['pin_verified' => true])->getJson('/api/transactions');
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

        $response = $this->withSession(['pin_verified' => true])->postJson('/api/transactions', $payload);
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

        $response = $this->withSession(['pin_verified' => true])->putJson('/api/transactions/' . $transaction->id, [
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

        $response = $this->withSession(['pin_verified' => true])->deleteJson('/api/transactions/' . $transaction->id);
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
        $response = $this->withSession(['pin_verified' => true])->get('/categories');
        $response->assertStatus(200);
        $response->assertSee('Categorie');

        // API create category
        $catResponse = $this->withSession(['pin_verified' => true])->postJson('/api/categories', [
            'name' => 'Nuova Categoria Test',
            'type' => 'expense',
            'icon' => '⭐',
            'color' => '#f59e0b'
        ]);
        $catResponse->assertStatus(201);
        $newCatId = $catResponse->json('category.id');

        // API create subcategory
        $subResponse = $this->withSession(['pin_verified' => true])->postJson('/api/categories/' . $newCatId . '/subcategories', [
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
        $response = $this->withSession(['pin_verified' => true])->get('/reports?year=2026');
        $response->assertStatus(200);
        $response->assertSee('Analisi Annuale');

        // Test CSV export
        $csvResponse = $this->withSession(['pin_verified' => true])->get('/reports/export-csv?year=2026');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_logout_locks_session(): void
    {
        $response = $this->withSession(['pin_verified' => true])->get('/logout');
        $response->assertRedirect(route('pin.show'));
        $this->assertFalse(session('pin_verified', false));
    }

    public function test_openapi_schema_is_public(): void
    {
        $response = $this->get('/api/v1/openapi.json');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'openapi',
            'info',
            'paths',
            'components' => [
                'securitySchemes'
            ]
        ]);
    }

    public function test_ai_api_requires_api_key(): void
    {
        $unauth = $this->getJson('/api/v1/overview');
        $unauth->assertStatus(401);
        $unauth->assertJson(['success' => false]);
    }

    public function test_ai_api_overview_with_valid_key(): void
    {
        $key = config('app.api_key');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
        ])->getJson('/api/v1/overview');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'period',
            'totals' => [
                'total_income',
                'total_expense',
                'net_balance',
                'savings_rate_percent'
            ],
            'expenses_by_category',
            'incomes_by_category'
        ]);
    }

    public function test_ai_api_create_transaction_with_smart_category(): void
    {
        $key = config('app.api_key');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $key,
        ])->postJson('/api/v1/transactions', [
            'type' => 'expense',
            'amount' => 18.50,
            'category_name' => 'Ristoranti',
            'description' => 'Pizza margherita e bibita',
            'payment_method' => 'Carta di Debito',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('transactions', [
            'description' => 'Pizza margherita e bibita',
            'amount' => 18.50,
        ]);
    }
}

