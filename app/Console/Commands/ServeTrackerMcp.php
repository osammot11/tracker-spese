<?php

namespace App\Console\Commands;

use App\Services\TrackerMcpTools;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ServeTrackerMcp extends Command
{
    protected $signature = 'mcp:serve';

    protected $description = 'Serve the personal expense tracker over MCP stdio';

    public function handle(TrackerMcpTools $tools): int
    {
        // MCP stdio uses one JSON-RPC message per line. Never print diagnostics to stdout.
        while (($line = fgets(STDIN)) !== false) {
            if (strlen($line) > 1024 * 1024) {
                $this->writeJson(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Messaggio troppo grande.']]);
                continue;
            }
            try {
                $message = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($message) || array_is_list($message)) {
                    throw new \JsonException('La richiesta deve essere un oggetto JSON-RPC.');
                }
            } catch (\JsonException $exception) {
                $this->writeJson(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => $exception->getMessage()]]);
                continue;
            }

            if (!array_key_exists('id', $message)) continue; // notification
            $id = $message['id'];
            if (!isset($message['method']) || !is_string($message['method'])) {
                $this->writeJson(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32600, 'message' => 'Metodo JSON-RPC mancante.']]);
                continue;
            }

            try {
                $result = match ($message['method']) {
                    'initialize' => [
                        'protocolVersion' => in_array($message['params']['protocolVersion'] ?? null, ['2025-11-25', '2025-06-18', '2025-03-26'], true)
                            ? $message['params']['protocolVersion'] : '2025-11-25',
                        'capabilities' => ['tools' => (object) []],
                        'serverInfo' => ['name' => 'tracker-spese', 'version' => '1.0.0'],
                        'instructions' => 'Tracker personale in euro. Usa list_categories prima di creare movimenti; usa list_transactions per identificare gli ID prima di modifiche o eliminazioni. Per cancellare una categoria, verifica quante transazioni contiene: la cancellazione elimina anche quelle. Le date sono YYYY-MM-DD, fuso Europe/Rome.',
                    ],
                    'ping' => (object) [],
                    'tools/list' => ['tools' => $tools->definitions()],
                    'tools/call' => $this->callTool($tools, $message['params'] ?? []),
                    default => throw new \InvalidArgumentException('Metodo MCP sconosciuto.'),
                };
                $this->writeJson(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
            } catch (\InvalidArgumentException $exception) {
                $this->writeJson(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => $exception->getMessage()]]);
            } catch (\Throwable $exception) {
                // Do not leak SQL, file paths, or configuration details to the MCP client.
                error_log('Tracker MCP: '.get_class($exception).': '.$exception->getMessage());
                $this->writeJson(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32603, 'message' => 'Errore interno del server MCP.']]);
            }
        }

        return self::SUCCESS;
    }

    private function callTool(TrackerMcpTools $tools, mixed $params): array
    {
        if (!is_array($params) || !is_string($params['name'] ?? null) || !is_array($params['arguments'] ?? [])) {
            throw new \InvalidArgumentException('Parametri di tools/call non validi.');
        }

        try {
            $result = $tools->call($params['name'], $params['arguments'] ?? []);
            return ['content' => [['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]], 'structuredContent' => $result];
        } catch (ValidationException $exception) {
            return $this->toolError($exception->errors());
        } catch (ModelNotFoundException $exception) {
            return $this->toolError(['id' => ['Elemento non trovato.']]);
        }
    }

    private function toolError(array $errors): array
    {
        return ['content' => [['type' => 'text', 'text' => json_encode(['errors' => $errors], JSON_UNESCAPED_UNICODE)]], 'isError' => true];
    }

    private function writeJson(array $message): void
    {
        fwrite(STDOUT, json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        fflush(STDOUT);
    }
}
