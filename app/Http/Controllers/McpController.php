<?php

namespace App\Http\Controllers;

use App\Services\TrackerMcpTools;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class McpController extends Controller
{
    public function handle(Request $request, string $token, TrackerMcpTools $tools): JsonResponse|Response
    {
        $expected = (string) config('app.mcp_access_token');
        if (strlen($expected) < 43 || !hash_equals($expected, $token)) {
            abort(404);
        }

        // Reject browser-originated requests from unrelated sites.
        $origin = $request->header('Origin');
        $allowed = array_filter(array_map('trim', explode(',', (string) config('app.mcp_allowed_origins'))));
        if ($origin !== null && !in_array($origin, $allowed, true)) {
            abort(403);
        }

        if (!$request->isMethod('POST')) {
            return response('', 405, ['Allow' => 'POST', 'Cache-Control' => 'no-store']);
        }
        if (!$request->isJson() || strlen($request->getContent()) > 1024 * 1024) {
            return response()->json(['error' => 'Richiesta JSON non valida o troppo grande.'], 400);
        }

        $protocolVersion = $request->header('MCP-Protocol-Version');
        if ($protocolVersion !== null && !in_array($protocolVersion, ['2025-03-26', '2025-06-18', '2025-11-25'], true)) {
            return response()->json(['error' => 'Versione MCP non supportata.'], 400);
        }

        try {
            $message = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->rpcError(null, -32700, 'JSON non valido.');
        }
        if (!is_array($message) || array_is_list($message) || ($message['jsonrpc'] ?? null) !== '2.0') {
            return $this->rpcError(null, -32600, 'Richiesta JSON-RPC non valida.');
        }
        if (!array_key_exists('id', $message)) {
            return response('', 202, ['Cache-Control' => 'no-store']);
        }
        $id = $message['id'];
        $method = $message['method'] ?? null;
        if (!is_string($method)) {
            return $this->rpcError($id, -32600, 'Metodo JSON-RPC mancante.');
        }

        try {
            $result = match ($method) {
                'initialize' => [
                    'protocolVersion' => in_array($message['params']['protocolVersion'] ?? null, ['2025-03-26', '2025-06-18', '2025-11-25'], true)
                        ? $message['params']['protocolVersion'] : '2025-11-25',
                    'capabilities' => ['tools' => (object) []],
                    'serverInfo' => ['name' => 'tracker-spese', 'version' => '1.1.0'],
                    'instructions' => 'Tracker personale in euro. Usa list_categories prima di creare movimenti e list_transactions per trovare gli ID prima di modifiche o eliminazioni. delete_category elimina anche le transazioni collegate. Date nel formato YYYY-MM-DD, fuso Europe/Rome.',
                ],
                'ping' => (object) [],
                'tools/list' => ['tools' => $tools->definitions()],
                'tools/call' => $this->callTool($tools, $message['params'] ?? []),
                default => throw new \InvalidArgumentException('Metodo MCP sconosciuto.'),
            };
            return $this->rpcResult($id, $result);
        } catch (\InvalidArgumentException $exception) {
            return $this->rpcError($id, -32601, $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            return $this->rpcError($id, -32603, 'Errore interno del server MCP.');
        }
    }

    private function callTool(TrackerMcpTools $tools, mixed $params): array
    {
        if (!is_array($params) || !is_string($params['name'] ?? null) || !is_array($params['arguments'] ?? [])) {
            throw new \InvalidArgumentException('Parametri di tools/call non validi.');
        }
        try {
            $data = $tools->call($params['name'], $params['arguments'] ?? []);
            return [
                'content' => [['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]],
                'structuredContent' => $data,
            ];
        } catch (ValidationException $exception) {
            $message = ['errors' => $exception->errors()];
            return ['content' => [['type' => 'text', 'text' => json_encode($message, JSON_UNESCAPED_UNICODE)]], 'isError' => true];
        } catch (ModelNotFoundException) {
            return ['content' => [['type' => 'text', 'text' => '{"error":"Elemento non trovato."}']], 'isError' => true];
        }
    }

    private function rpcResult(mixed $id, mixed $result): JsonResponse
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result])
            ->header('Cache-Control', 'no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function rpcError(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]])
            ->header('Cache-Control', 'no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
