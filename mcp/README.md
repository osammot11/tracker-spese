# Server MCP del Tracker Spese

Il comando `php artisan mcp:serve` espone 12 strumenti MCP tramite **stdio**. Usa il database e la configurazione Laravel del tracker, quindi non serve avviare `php artisan serve` e non serve copiare la chiave `CHATGPT_API_KEY` in ChatGPT.

## Strumenti

| Lettura | Scrittura |
| --- | --- |
| `get_monthly_overview`, `get_annual_report` | `create_transaction`, `update_transaction`, `delete_transaction` |
| `list_transactions`, `list_categories` | `create_category`, `update_category`, `delete_category`, `create_subcategory`, `delete_subcategory` |

Gli importi sono in euro; le date sono `YYYY-MM-DD`. Prima di scrivere una transazione, usa `list_categories` per ottenere `category_id`. Prima di modificare o eliminare una transazione, usa `list_transactions` per verificare l'ID. `delete_category` elimina anche le transazioni della categoria per effetto della cascata del database; richiede `confirm_delete: true`.

## Verifica locale

Dal progetto:

```sh
php artisan migrate --force
php artisan test tests/Feature/TrackerMcpTest.php
printf '%s\n' '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"test","version":"1"}}}' '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' | ./mcp/start.sh
```

L'ultima riga restituisce due risposte JSON-RPC; la seconda contiene i 12 strumenti. I test usano un database temporaneo e non cambiano i dati personali.

## Collegamento a ChatGPT tramite Secure MCP Tunnel

Il tracker conserva dati personali su questo computer. Il percorso consigliato è [Secure MCP Tunnel](https://developers.openai.com/api/docs/guides/secure-mcp-tunnels): ChatGPT raggiunge il processo locale senza pubblicare il database o aprire una porta Internet in ingresso.

1. In [Platform tunnel settings](https://platform.openai.com/settings/organization/tunnels), crea un tunnel e associalo all'organizzazione Platform **e** allo spazio ChatGPT dove vuoi usare il tracker. Servono i permessi Tunnels Read + Manage per crearlo e Tunnels Read + Use per eseguirlo.
2. Installa `tunnel-client` seguendo la [guida ufficiale](https://developers.openai.com/api/docs/guides/secure-mcp-tunnels). Conserva la sua chiave runtime fuori dal repository e dalla cronologia della shell. Avvia la configurazione con `tunnel-client help quickstart`.
3. Configura un profilo stdio, sostituendo il percorso e l'ID con quelli reali:

   ```sh
   tunnel-client init --sample sample_mcp_stdio_local \
     --profile tracker-spese \
     --tunnel-id tunnel_ID_REALE \
     --mcp-command '/percorso/assoluto/tracker-spese/mcp/start.sh'
   tunnel-client doctor --profile tracker-spese --explain
   tunnel-client run --profile tracker-spese
   ```

   Il percorso può contenere spazi. Prima di `init`, imposta `CONTROL_PLANE_API_KEY` nell'ambiente del terminale in modo riservato, come indicato dalla guida ufficiale. Tieni attivi il computer e `tunnel-client run` quando vuoi usare gli strumenti.
4. In [ChatGPT Plugins](https://chatgpt.com/plugins), scegli **Add custom MCP server**, **Connection: Tunnel**, seleziona il tunnel e configura **No authentication** per il server locale (l'accesso al tunnel è controllato dall'associazione e dalla chiave runtime). Crea e installa il plugin privato.
5. Apri una nuova chat, seleziona il plugin con `@`, poi prova una lettura: “Mostrami le categorie del Tracker Spese”. Verifica gli argomenti prima delle azioni di scrittura.

Se il tunnel non compare, controlla associazione dello spazio ChatGPT, permessi Tunnels Read + Use e stato di `tunnel-client doctor`.

## Client MCP locali

Un client che supporta MCP stdio può eseguire direttamente `mcp/start.sh` come comando. Specifica sempre il **percorso assoluto** dello script, ad esempio:

```json
{
  "mcpServers": {
    "tracker-spese": {
      "command": "/percorso/assoluto/tracker-spese/mcp/start.sh",
      "args": []
    }
  }
}
```

Il server stdio va avviato solo da client MCP locali fidati: non ha una propria autenticazione di rete. Non esporlo tramite proxy HTTP anonimo.
