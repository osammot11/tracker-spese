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
                "description" => "API per gestire e interrogare spese, entrate, bilanci e categorie del tracker personale.",
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
                        "summary" => "Ottieni il riepilogo finanziario mensile",
                        "description" => "Restituisce totale entrate, totale uscite, saldo netto, tasso di risparmio e ripartizione per categorie.",
                        "parameters" => [
                            [
                                "name" => "month",
                                "in" => "query",
                                "description" => "Numero del mese (1-12). Default: mese corrente.",
                                "required" => false,
                                "schema" => ["type" => "integer", "example" => 8],
                            ],
                            [
                                "name" => "year",
                                "in" => "query",
                                "description" => "Anno a 4 cifre (es. 2026). Default: anno corrente.",
                                "required" => false,
                                "schema" => ["type" => "integer", "example" => 2026],
                            ],
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Panoramica finanziaria recuperata con successo.",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "period" => ["type" => "object"],
                                                "totals" => ["type" => "object"],
                                                "expenses_by_category" => ["type" => "array", "items" => ["type" => "object"]],
                                                "incomes_by_category" => ["type" => "array", "items" => ["type" => "object"]],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "/api/v1/transactions" => [
                    "get" => [
                        "operationId" => "listTransactions",
                        "summary" => "Cerca e filtra le transazioni",
                        "description" => "Filtra le transazioni registrate per tipo, categoria, data o testo di ricerca.",
                        "parameters" => [
                            [
                                "name" => "type",
                                "in" => "query",
                                "description" => "Tipo: expense o income",
                                "required" => false,
                                "schema" => ["type" => "string", "enum" => ["expense", "income"]],
                            ],
                            [
                                "name" => "category_name",
                                "in" => "query",
                                "description" => "Filtra per nome categoria",
                                "required" => false,
                                "schema" => ["type" => "string"],
                            ],
                            [
                                "name" => "search",
                                "in" => "query",
                                "description" => "Testo di ricerca",
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
                                "description" => "Numero massimo di transazioni (default: 20)",
                                "required" => false,
                                "schema" => ["type" => "integer"],
                            ],
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Lista transazioni",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "count" => ["type" => "integer"],
                                                "transactions" => ["type" => "array", "items" => ['$ref' => "#/components/schemas/TransactionItem"]],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "post" => [
                        "operationId" => "createTransaction",
                        "summary" => "Registra una nuova spesa o entrata",
                        "description" => "Inserisce una transazione specificando importo, tipo ed eventualmente categoria.",
                        "requestBody" => [
                            "required" => true,
                            "content" => [
                                "application/json" => [
                                    "schema" => ['$ref' => "#/components/schemas/CreateTransactionRequest"],
                                ],
                            ],
                        ],
                        "responses" => [
                            "201" => [
                                "description" => "Transazione creata",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "success" => ["type" => "boolean"],
                                                "message" => ["type" => "string"],
                                                "transaction" => ['$ref' => "#/components/schemas/TransactionItem"],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "/api/v1/transactions/{id}" => [
                    "delete" => [
                        "operationId" => "deleteTransaction",
                        "summary" => "Elimina una transazione esistente",
                        "parameters" => [
                            [
                                "name" => "id",
                                "in" => "path",
                                "required" => true,
                                "description" => "ID numerico della transazione",
                                "schema" => ["type" => "integer"],
                            ],
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Transazione eliminata",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "success" => ["type" => "boolean"],
                                                "message" => ["type" => "string"],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "/api/v1/categories" => [
                    "get" => [
                        "operationId" => "listCategories",
                        "summary" => "Elenca le categorie e sottocategorie",
                        "responses" => [
                            "200" => [
                                "description" => "Lista categorie",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "categories" => [
                                                    "type" => "array",
                                                    "items" => ['$ref' => "#/components/schemas/CategoryItem"],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "post" => [
                        "operationId" => "createCategory",
                        "summary" => "Crea una nuova categoria",
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
                                            "icon" => ["type" => "string", "description" => "Emoji (es. 🍕, 🚗, 🏋️)"],
                                            "color" => ["type" => "string", "description" => "Colore HEX (es. #10b981)"],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "responses" => [
                            "201" => [
                                "description" => "Categoria creata",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "success" => ["type" => "boolean"],
                                                "message" => ["type" => "string"],
                                                "category" => ['$ref' => "#/components/schemas/CategoryItem"],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "/api/v1/categories/{category}/subcategories" => [
                    "post" => [
                        "operationId" => "createSubcategory",
                        "summary" => "Aggiunge una sottocategoria",
                        "parameters" => [
                            [
                                "name" => "category",
                                "in" => "path",
                                "required" => true,
                                "description" => "ID numerico della categoria",
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
                                            "name" => ["type" => "string", "description" => "Nome della sottocategoria"],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "responses" => [
                            "201" => [
                                "description" => "Sottocategoria creata",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "success" => ["type" => "boolean"],
                                                "message" => ["type" => "string"],
                                                "subcategory" => ["type" => "object"],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            "components" => [
                "schemas" => [
                    "CreateTransactionRequest" => [
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
                                "description" => "Importo numerico in Euro (es. 25.50)",
                                "example" => 25.50,
                            ],
                            "category_name" => [
                                "type" => "string",
                                "description" => "Nome della categoria (es. Alimentari, Ristoranti, Casa, Trasporti, Stipendio)",
                                "example" => "Ristoranti & Svago",
                            ],
                            "subcategory_name" => [
                                "type" => "string",
                                "description" => "Nome della sottocategoria specifica (es. Pizzeria, Supermercato, Benzina)",
                                "example" => "Pizzeria",
                            ],
                            "description" => [
                                "type" => "string",
                                "description" => "Descrizione o causale (es. Pizza con colleghi, Spesa Esselunga)",
                                "example" => "Pizza con colleghi",
                            ],
                            "date" => [
                                "type" => "string",
                                "description" => "Data nel formato YYYY-MM-DD (se omessa viene impostata la data odierna)",
                                "example" => "2026-08-18",
                            ],
                            "payment_method" => [
                                "type" => "string",
                                "description" => "Metodo: Carta di Debito, Contanti, Carta di Credito, Bonifico Bancario, PayPal, Altro",
                                "example" => "Carta di Debito",
                            ],
                            "notes" => [
                                "type" => "string",
                                "description" => "Note opzionali",
                            ],
                        ],
                    ],
                    "TransactionItem" => [
                        "type" => "object",
                        "properties" => [
                            "id" => ["type" => "integer"],
                            "type" => ["type" => "string", "enum" => ["expense", "income"]],
                            "amount" => ["type" => "number"],
                            "date" => ["type" => "string"],
                            "category" => ["type" => "string"],
                            "subcategory" => ["type" => "string"],
                            "description" => ["type" => "string"],
                            "payment_method" => ["type" => "string"],
                        ],
                    ],
                    "CategoryItem" => [
                        "type" => "object",
                        "properties" => [
                            "id" => ["type" => "integer"],
                            "name" => ["type" => "string"],
                            "type" => ["type" => "string"],
                            "icon" => ["type" => "string"],
                            "color" => ["type" => "string"],
                            "subcategories" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "object",
                                    "properties" => [
                                        "id" => ["type" => "integer"],
                                        "name" => ["type" => "string"],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "securitySchemes" => [
                    "BearerAuth" => [
                        "type" => "http",
                        "scheme" => "bearer",
                        "description" => "Inserisci la chiave CHATGPT_API_KEY",
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
