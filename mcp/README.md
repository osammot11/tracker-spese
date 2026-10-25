# Server MCP del Tracker Spese

Il tracker espone un endpoint **MCP Streamable HTTP** direttamente dal sito Laravel. ChatGPT lo raggiunge tramite l'URL HTTPS della VPS: non richiede OpenAI Platform, chiavi API OpenAI, tunnel o processi aggiuntivi. Il server usa i modelli e il database del tracker.

## Attivazione sulla VPS

1. Verifica che il sito usi HTTPS e che la VPS abbia già ricevuto l'ultima versione del codice.
2. Genera un token casuale sul server:

   ```sh
   php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
   ```

3. Aggiungi il valore ottenuto nel `.env` **della VPS**:

   ```dotenv
   MCP_ACCESS_TOKEN=IL_TOKEN_CASUALE_DA_64_CARATTERI
   MCP_ALLOWED_ORIGINS=https://chatgpt.com
   ```

   Conserva questo token come una password: chi conosce l'URL completo può leggere e modificare i dati del tracker. Non commetterlo in Git, non condividerlo in chat e limita o oscura la registrazione del percorso `/mcp/` nei log del web server.

4. Ricarica la configurazione Laravel:

   ```sh
   php artisan optimize:clear
   php artisan config:cache
   ```

5. L'URL da inserire in ChatGPT è:

   ```text
   https://TUO-DOMINIO/mcp/IL_TOKEN_CASUALE_DA_64_CARATTERI
   ```

   Il token non appare nella documentazione o nella risposta del server. Un URL errato restituisce 404.

## Collegamento a ChatGPT

Apri [ChatGPT Plugins](https://chatgpt.com/plugins), scegli **Add custom MCP server**, seleziona **Server URL** e incolla l'URL HTTPS completo. Scegli **No authentication**: l'URL casuale è la credenziale del server. Crea e installa il plugin privato. In una nuova chat, selezionalo con `@` e prova una lettura: “Mostrami le categorie del Tracker Spese”.

ChatGPT supporta [server MCP personalizzati tramite Streamable HTTP](https://developers.openai.com/api/docs/guides/custom-mcp-server). L'endpoint restituisce JSON-RPC e non usa la vecchia API REST `/api/v1`.

## Strumenti disponibili

| Lettura | Scrittura |
| --- | --- |
| `get_monthly_overview`, `get_annual_report` | `create_transaction`, `update_transaction`, `delete_transaction` |
| `list_transactions`, `list_categories` | `create_category`, `update_category`, `delete_category`, `create_subcategory`, `delete_subcategory` |

Gli importi sono in euro e le date sono `YYYY-MM-DD`. Prima di creare una transazione usa `list_categories` per ottenere `category_id`. Prima di modificare o eliminare una transazione usa `list_transactions` per verificare l'ID. `delete_category` elimina anche le transazioni della categoria per effetto della cascata del database e richiede `confirm_delete: true`.

## Verifica locale

I test non modificano i dati personali:

```sh
php artisan test tests/Feature/TrackerMcpTest.php tests/Feature/TrackerMcpHttpTest.php
```

Il comando `php artisan mcp:serve` e lo script `mcp/start.sh` restano disponibili per client MCP **locali** che usano stdio; ChatGPT sulla VPS usa l'endpoint HTTPS sopra.
