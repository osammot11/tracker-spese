<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackerMcpHttpTest extends TestCase
{
    use RefreshDatabase;

    private string $token = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.mcp_access_token' => $this->token, 'app.mcp_allowed_origins' => 'https://chatgpt.com']);
    }

    private function callMcp(string $method, array $params = [], int $id = 1)
    {
        return $this->withHeaders([
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2025-11-25',
        ])->postJson('/mcp/'.$this->token, ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params]);
    }

    public function test_http_initialize_list_and_read_tool(): void
    {
        Category::create(['name' => 'Alimentari', 'type' => 'expense']);

        $init = $this->callMcp('initialize', ['protocolVersion' => '2025-11-25', 'capabilities' => [], 'clientInfo' => ['name' => 'test', 'version' => '1']]);
        $init->assertOk()->assertJsonPath('result.protocolVersion', '2025-11-25');
        $this->assertStringContainsString('no-store', $init->headers->get('Cache-Control'));

        $list = $this->callMcp('tools/list');
        $list->assertOk()->assertJsonCount(12, 'result.tools');

        $call = $this->callMcp('tools/call', ['name' => 'list_categories', 'arguments' => []]);
        $call->assertOk()->assertJsonPath('result.structuredContent.categories.0.name', 'Alimentari');
    }

    public function test_secret_and_origin_are_checked_before_tool_access(): void
    {
        $body = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
        $this->postJson('/mcp/'.str_repeat('a', 64), $body)->assertNotFound();

        config(['app.mcp_access_token' => '']);
        $this->postJson('/mcp/'.$this->token, $body)->assertNotFound();
        config(['app.mcp_access_token' => $this->token]);

        $this->withHeaders(['Origin' => 'https://evil.example'])->postJson('/mcp/'.$this->token, $body)->assertForbidden();
        $this->withHeaders(['Origin' => 'https://chatgpt.com'])->postJson('/mcp/'.$this->token, $body)->assertOk();
    }

    public function test_notifications_and_get_follow_streamable_http_contract(): void
    {
        $this->withHeaders(['Accept' => 'text/event-stream'])->get('/mcp/'.$this->token)->assertStatus(405);
        $this->postJson('/mcp/'.$this->token, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])->assertStatus(202);
        $this->withHeaders(['MCP-Protocol-Version' => 'invalid'])->postJson('/mcp/'.$this->token, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertBadRequest();
    }

    public function test_write_tool_persists_a_transaction(): void
    {
        $category = Category::create(['name' => 'Trasporti', 'type' => 'expense']);
        $response = $this->callMcp('tools/call', [
            'name' => 'create_transaction',
            'arguments' => [
                'type' => 'expense', 'amount' => 12.75,
                'category_id' => $category->id, 'date' => '2026-10-25',
                'description' => 'Biglietto treno',
            ],
        ]);

        $response->assertOk()->assertJsonPath('result.structuredContent.transaction.amount', 12.75);
        $this->assertDatabaseHas('transactions', ['description' => 'Biglietto treno', 'amount' => 12.75]);
    }
}
