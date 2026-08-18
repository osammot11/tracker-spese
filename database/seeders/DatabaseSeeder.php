<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => 'Casa & Utenze',
                'type' => 'expense',
                'icon' => '🏠',
                'color' => '#3b82f6',
                'subcategories' => ['Affitto / Mutuo', 'Bollette Luce & Gas', 'Internet & Telefono', 'Manutenzione & Riparazioni', 'Arredamento & Accessori']
            ],
            [
                'name' => 'Spesa & Alimentari',
                'type' => 'expense',
                'icon' => '🛒',
                'color' => '#10b981',
                'subcategories' => ['Supermercato', 'Mercato rionale', 'Panificio & Forno', 'Macelleria & Pescheria']
            ],
            [
                'name' => 'Ristoranti & Svago',
                'type' => 'expense',
                'icon' => '🍽️',
                'color' => '#f59e0b',
                'subcategories' => ['Ristoranti & Pizzerie', 'Bar & Colazioni', 'Cinema, Concerti & Eventi', 'Aperitivi & Serate', 'Food Delivery']
            ],
            [
                'name' => 'Trasporti & Veicoli',
                'type' => 'expense',
                'icon' => '🚗',
                'color' => '#6366f1',
                'subcategories' => ['Carburante', 'Mezzi Pubblici & Treni', 'Assicurazione & Bollo', 'Tagliando & Manutenzione', 'Parcheggi & Pedaggi']
            ],
            [
                'name' => 'Salute & Benessere',
                'type' => 'expense',
                'icon' => '💊',
                'color' => '#ec4899',
                'subcategories' => ['Visite Mediche & Esami', 'Farmacia', 'Palestra & Sport', 'Dentista', 'Cura Personale']
            ],
            [
                'name' => 'Shopping & Abbigliamento',
                'type' => 'expense',
                'icon' => '🛍️',
                'color' => '#8b5cf6',
                'subcategories' => ['Abbigliamento & Scarpe', 'Elettronica & Gadget', 'Libri & Riviste', 'Articoli per la Casa']
            ],
            [
                'name' => 'Viaggi & Vacanze',
                'type' => 'expense',
                'icon' => '✈️',
                'color' => '#06b6d4',
                'subcategories' => ['Voli & Treni', 'Hotel & Alloggi', 'Tour & Musei', 'Pasti in vacanza']
            ],
            [
                'name' => 'Abbonamenti & Servizi',
                'type' => 'expense',
                'icon' => '📱',
                'color' => '#f43f5e',
                'subcategories' => ['Streaming (Netflix, Spotify)', 'Software, Cloud & AI', 'Hosting & Domini']
            ],
            [
                'name' => 'Stipendio & Lavoro',
                'type' => 'income',
                'icon' => '💼',
                'color' => '#059669',
                'subcategories' => ['Stipendio Mensile', 'Tredicesima / Quattordicesima', 'Bonus & Premi']
            ],
            [
                'name' => 'Freelance & Extra',
                'type' => 'income',
                'icon' => '💻',
                'color' => '#0d9488',
                'subcategories' => ['Progetti Freelance', 'Consulenze Tecniche', 'Vendite Usato']
            ],
            [
                'name' => 'Investimenti & Rendite',
                'type' => 'income',
                'icon' => '📈',
                'color' => '#0284c7',
                'subcategories' => ['Dividendi & Azioni', 'Interessi Conto Deposito', 'Affitti']
            ],
            [
                'name' => 'Rimborsi & Regali',
                'type' => 'income',
                'icon' => '🎁',
                'color' => '#7c3aed',
                'subcategories' => ['Regali Ricevuti', 'Rimborsi Spese Lavoro', 'Cashback']
            ],
        ];

        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        $createdCategories = [];
        $createdSubcategories = [];

        foreach ($categoriesData as $catData) {
            $subcats = $catData['subcategories'];
            unset($catData['subcategories']);

            $category = Category::create($catData);
            $createdCategories[$category->name] = $category;

            foreach ($subcats as $subName) {
                $sub = Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $subName,
                ]);
                $createdSubcategories[$category->name . ' - ' . $subName] = $sub;
            }
        }

        // Sample Transactions for Current Month
        $sampleTransactions = [
            // Incomes
            [
                'type' => 'income',
                'category' => 'Stipendio & Lavoro',
                'subcategory' => 'Stipendio Mensile',
                'amount' => 2450.00,
                'day' => 1,
                'description' => 'Accredito stipendio mensile',
                'payment_method' => 'Bonifico Bancario',
            ],
            [
                'type' => 'income',
                'category' => 'Freelance & Extra',
                'subcategory' => 'Progetti Freelance',
                'amount' => 650.00,
                'day' => 10,
                'description' => 'Sviluppo sito web cliente',
                'payment_method' => 'Bonifico Bancario',
            ],
            [
                'type' => 'income',
                'category' => 'Investimenti & Rendite',
                'subcategory' => 'Dividendi & Azioni',
                'amount' => 84.50,
                'day' => 15,
                'description' => 'Dividendi trimestrali ETF',
                'payment_method' => 'Altro',
            ],

            // Expenses
            [
                'type' => 'expense',
                'category' => 'Casa & Utenze',
                'subcategory' => 'Affitto / Mutuo',
                'amount' => 750.00,
                'day' => 2,
                'description' => 'Affitto appartamento',
                'payment_method' => 'Bonifico Bancario',
            ],
            [
                'type' => 'expense',
                'category' => 'Casa & Utenze',
                'subcategory' => 'Bollette Luce & Gas',
                'amount' => 115.40,
                'day' => 5,
                'description' => 'Bolletta Enel bimestrale',
                'payment_method' => 'Addebito Diretto',
            ],
            [
                'type' => 'expense',
                'category' => 'Spesa & Alimentari',
                'subcategory' => 'Supermercato',
                'amount' => 142.80,
                'day' => 3,
                'description' => 'Spesa settimanale Esselunga',
                'payment_method' => 'Carta di Debito',
            ],
            [
                'type' => 'expense',
                'category' => 'Spesa & Alimentari',
                'subcategory' => 'Supermercato',
                'amount' => 98.30,
                'day' => 10,
                'description' => 'Spesa supermercato e prodotti freschi',
                'payment_method' => 'Carta di Debito',
            ],
            [
                'type' => 'expense',
                'category' => 'Trasporti & Veicoli',
                'subcategory' => 'Carburante',
                'amount' => 70.00,
                'day' => 6,
                'description' => 'Pieno benzina auto',
                'payment_method' => 'Carta di Credito',
            ],
            [
                'type' => 'expense',
                'category' => 'Ristoranti & Svago',
                'subcategory' => 'Ristoranti & Pizzerie',
                'amount' => 55.00,
                'day' => 7,
                'description' => 'Cena pizzeria con amici',
                'payment_method' => 'Carta di Debito',
            ],
            [
                'type' => 'expense',
                'category' => 'Ristoranti & Svago',
                'subcategory' => 'Bar & Colazioni',
                'amount' => 18.50,
                'day' => 9,
                'description' => 'Caffè e colazioni settimana',
                'payment_method' => 'Contanti',
            ],
            [
                'type' => 'expense',
                'category' => 'Salute & Benessere',
                'subcategory' => 'Farmacia',
                'amount' => 32.60,
                'day' => 11,
                'description' => 'Integratori e sciroppo',
                'payment_method' => 'Carta di Debito',
            ],
            [
                'type' => 'expense',
                'category' => 'Abbonamenti & Servizi',
                'subcategory' => 'Streaming (Netflix, Spotify)',
                'amount' => 17.99,
                'day' => 12,
                'description' => 'Abbonamento Netflix Premium',
                'payment_method' => 'PayPal',
            ],
            [
                'type' => 'expense',
                'category' => 'Shopping & Abbigliamento',
                'subcategory' => 'Abbigliamento & Scarpe',
                'amount' => 89.90,
                'day' => 14,
                'description' => 'Scarpe da ginnastica',
                'payment_method' => 'Carta di Credito',
            ],
        ];

        foreach ($sampleTransactions as $item) {
            $cat = $createdCategories[$item['category']] ?? null;
            $subcatKey = $item['category'] . ' - ' . $item['subcategory'];
            $sub = $createdSubcategories[$subcatKey] ?? null;

            if ($cat) {
                // Ensure day is valid for current month
                $day = min($item['day'], (int) $now->daysInMonth);
                $date = Carbon::createFromDate($currentYear, $currentMonth, $day);

                Transaction::create([
                    'type' => $item['type'],
                    'category_id' => $cat->id,
                    'subcategory_id' => $sub ? $sub->id : null,
                    'amount' => $item['amount'],
                    'date' => $date->format('Y-m-d'),
                    'description' => $item['description'],
                    'payment_method' => $item['payment_method'],
                ]);
            }
        }
    }
}
