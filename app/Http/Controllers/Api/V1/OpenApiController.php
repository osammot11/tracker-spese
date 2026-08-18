<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OpenApiController extends Controller
{
    public function schema(Request $request)
    {
        $serverUrl = url("/");

        $schema = [
            "openapi" => "3.1.0",
            "info" => [
                "title" => "Tracker Spese Personali API",
                "description" => "API per interrogare, creare e gestire spese, entrate, bilanci e categorie del tracker personale.",
                "version" => "1.0.0",
            ],
            "servers" => [
                [
                    "url" => $serverUrl,
                    "description" => "Server Tracker Spese",
                ],
            ],
            "paths" => [
                "/api/v1/overview" => [
                    "get" => [
                        "operationId" => "getFinancialOverview",
                        "summary" => "Ottieni il riepilogo finanziario mensile (entrate, uscite, saldo, ripartizione per categoria)",
                        "parameters" => [
                            [
                                "name" => "month",
                                "in" => "query",
                                "description" => "Numero del mese (1-12). Se omesso usa il mese corrente.",
                                "required" => false,
                                "schema" => ["type" => "integer", "example" => 8],
                            ],
                            [
                                "name" => "year",
                                "in" => "query",
                                "description" => "Anno a 4 cifre (es. 2026). Se omesso usa l anno corrente.",
                                "required" => false,
                                "schema" => ["type" => "integer", "example" => 2026],
                            ],
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Panoramica finanziaria recuperata con successo.",
                            ],
                        ],
                    ],
                ],
                "/api/v1/transactions" => [
                    "get" => [
                        "operationId" => "listTransactions",
                        "summary" => "Cerca e filtra le transazioni (per data, tipo, categoria, testo di ricerca)",
                        "parameters" => [
                            [
                                "name" => "type",
                                "in" => "query",
                                "description" => "Tipo di transazione: expense (spesa) o income (entrata)",
                                "required" => false,
                                "schema" => ["type" => "string", "enum" => ["expense", "income"]],
                            ],
                            [
                                "name" => "category_name",
                                "in" => "query",
                                "description" => "Filtra per nome categoria (es. Alimentari, Ristoranti, Casa)",
                                "required" => false,
                                "schema" => ["type" => "string"],
                            ],
                            [
                                "name" => "search",
                                "in" => "query",
                                "description" => "Testo di ricerca nella descrizione, note o metodo di pagamento",
                                "required" => false,
                                "schema" => ["type" => "string"],
                            ],
                            [
                                "name" => "month",
                                "in" => "query",
                                "description" => "Mese (1-12)",
                                "required" => false,
                                "schema" => ["type" => "integer"],
                            ],
                            [
                                "name" => "year",
                                "in" => "query",
                                "description" => "Anno (es. 2026)",
                                "required" => false,
                                "schema" => ["type" => "integer"],
                            ],
                            [
                                "name" => "limit",
                                "in" => "query",
                                "description" => "Numero massimo di transazioni da restituire (default: 20)",
                                "required" => false,
                                "schema" => ["type" => "integer"],
                            ],
                        ],
                        "responses" => [
                            "200" => ["description" => "Lista transazioni trovate."],
                        ],
                    ],
                    "post" => [
                        "operationId" => "createTransaction",
                        "summary" => "Registra una nuova spesa o entrata nel tracker",
                        "requestBody" => [
                            "required" => true,
                            "content" => [
                                "application/json" => [
                                    "schema" => [
                                        "type" => "object",
                                        "required" => ["type", "amount"],
                                        "properties" => [
                                            "type" => [
                                                "type" => "string",
                                                "enum" => ["expense", "income"],
                                                "description" => "expense per spesa/uscita, income per entrata/stipendio",
                                            ],
                                            "amount" => [
                                                "type" => "number",
                                                "description" => "Importo numerico in Euro (es. 24.50, 750, 1200)",
                                                "example" => 25.50,
                                            ],
                                            "category_name" => [
                                                "type" => "string",
                                                "description" => "Nome della categoria (es. Alimentari, Ristoranti, Trasporti, Casa, Salute, Stipendio, Freelance). L API la abbinera automaticamente.",
                                                "example" => "Ristoranti & Svago",
                                            ],
                                            "subcategory_name" => [
                                                "type" => "string",
                                                "description" => "Nome della sottocategoria specifica (es. Pizzeria, Supermercato, Benzina, Bollette, ecc.)",
                                                "example" => "Pizzeria",
                                            ],
                                            "description" => [
                                                "type" => "string",
                                                "description" => "Descrizione o causale della spesa/entrata (es. Pizza con colleghi, Stipendio mese, Spesa Esselunga)",
                                                "example" => "Pizza con colleghi",
                                            ],
                                            "date" => [
                                                "type" => "string",
                                                "format" => "date",
                                                "description" => "Data nel formato YYYY-MM-DD (se omessa, viene impostata la data odierna)",
                                                "example" => "2026-08-18",
                                            ],
                                            "payment_method" => [
                                                "type" => "string",
                                                "description" => "Metodo usato: Carta di Debito, Contanti, Carta di Credito, Bonifico Bancario, PayPal, Altro",
                                                "example" => "Carta di Debito",
                                            ],
                                            "notes" => [
                                                "type" => "string",
                                                "description" => "Note opzionali aggiuntive",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "responses" => [
                            "201" => ["description" => "Transazione salvata con successo."],
                        ],
                    ],
                ],
                "/api/v1/transactions/{id}" => [
                    "delete" => [
                        "operationId" => "deleteTransaction",
                        "summary" => "Elimina una transazione esistente tramite il suo ID",
                        "parameters" => [
                            [
                                "name" => "id",
                                "in" => "path",
                                "required" => true,
                                "description" => "ID numerico della transazione da eliminare",
                                "schema" => ["type" => "integer"],
                            ],
                        ],
                        "responses" => [
                            "200" => ["description" => "Transazione eliminata con successo."],
                        ],
                    ],
                ],
                "/api/v1/categories" => [
                    "get" => [
                        "operationId" => "listCategories",
                        "summary" => "Elenca tutte le categorie e le relative sottocategorie disponibili",
                        "responses" => [
                            "200" => ["description" => "Lista categorie e sottocategorie."],
                        ],
                    ],
                    "post" => [
                        "operationId" => "createCategory",
                        "summary" => "Crea una nuova categoria di spesa o entrata",
                        "requestBody" => [
                            "required" => true,
                            "content" => [
                                "application/json" => [
                                    "schema" => [
                                        "type" => "object",
                                        "required" => ["name", "type"],
                                        "properties" => [
                                            "name" => ["type" => "string", "description" => "Nome della categoria"],
                                            "type" => ["type" => "string", "enum" => ["expense", "income", "both"]],
                                            "icon" => ["type" => "string", "description" => "Emoji rappresentativa (es. 🍕, 🚗, 🏋️)"],
                                            "color" => ["type" => "string", "description" => "Codice colore HEX (es. #10b981)"],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "responses" => [
                            "201" => ["description" => "Categoria creata con successo."],
                        ],
                    ],
                ],
                "/api/v1/categories/{category}/subcategories" => [
                    "post" => [
                        "operationId" => "createSubcategory",
                        "summary" => "Aggiunge una sottocategoria a una categoria esistente",
                        "parameters" => [
                            [
                                "name" => "category",
                                "in" => "path",
                                "required" => true,
                                "description" => "ID numerico della categoria padre",
                                "schema" => ["type" => "integer"],
                            ],
                        ],
                        "requestBody" => [
                            "required" => true,
                            "content" => [
                                "application/json" => [
                                    "schema" => [
                                        "type" => "object",
                                        "required" => ["name"],
                                        "properties" => [
                                            "name" => ["type" => "string", "description" => "Nome della nuova sottocategoria"],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "responses" => [
                            "201" => ["description" => "Sottocategoria creata con successo."],
                        ],
                    ],
                ],
            ],
            "components" => [
                "securitySchemes" => [
                    "BearerAuth" => [
                        "type" => "http",
                        "scheme" => "bearer",
                        "description" => "Inserisci la chiave CHATGPT_API_KEY configurata sul server.",
                    ],
                ],
            ],
            "security" => [
                ["BearerAuth" => []],
            ],
        ];

        return response()->json($schema, 200, [
            "Access-Control-Allow-Origin" => "*",
            "Access-Control-Allow-Methods" => "GET, OPTIONS",
        ]);
    }
}
