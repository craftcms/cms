<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\cache\FileCache;
use craft\elements\User;
use craft\helpers\FileHelper;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\services\Mcp;
use craft\test\TestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;

class HttpTransportTest extends TestCase
{
    private string $cachePath;
    private mixed $originalCache;
    private mixed $originalIdentity;

    protected function _before(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/craft-mcp-' . bin2hex(random_bytes(8));
        $this->originalCache = Craft::$app->getCache();
        $this->originalIdentity = Craft::$app->getUser()->getIdentity();
        Craft::$app->get('mcp')->getSettings()->publicTools = ['craft-context-get'];
    }

    protected function _after(): void
    {
        Craft::$app->set('cache', $this->originalCache);
        Craft::$app->getUser()->setIdentity($this->originalIdentity);
        FileHelper::removeDirectory($this->cachePath);
    }

    /** @dataProvider handshakeVersions */
    public function testHandshakeSessionSurvivesRequestsAndCanBeClosed(string $version): void
    {
        $response = $this->exchange('public', $this->initialize($version));
        self::assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        self::assertSame($version, json_decode((string)$response->getBody(), true)['result']['protocolVersion']);
        $session = $response->getHeaderLine('Mcp-Session-Id');
        self::assertNotEmpty($session);
        $headers = ['Mcp-Session-Id' => $session, 'MCP-Protocol-Version' => $version];

        $response = $this->exchange('public', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $headers);
        self::assertSame(202, $response->getStatusCode());

        $list = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'];
        $response = $this->exchange('public', $list, $headers);
        self::assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        self::assertSame(['craft-context-get'], array_column(json_decode((string)$response->getBody(), true)['result']['tools'], 'name'));

        // Public approvals still apply to an already initialized session.
        Craft::$app->get('mcp')->getSettings()->publicTools = [];
        $response = $this->exchange('public', $list, $headers);
        self::assertSame([], json_decode((string)$response->getBody(), true)['result']['tools']);

        self::assertSame(200, $this->exchange('public', [], $headers, 'DELETE')->getStatusCode());
        self::assertSame(404, $this->exchange('public', $list, $headers)->getStatusCode());
    }

    public static function handshakeVersions(): array
    {
        return [
            ['2025-03-26'],
            ['2025-06-18'],
            ['2025-11-25'],
        ];
    }

    public function testModernRequestsDoNotNeedAHandshakeOrSession(): void
    {
        $response = $this->exchange('public', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => ['_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object)[],
            ]],
        ], ['MCP-Protocol-Version' => '2026-07-28', 'Mcp-Method' => 'tools/list']);

        self::assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        self::assertSame('', $response->getHeaderLine('Mcp-Session-Id'));
        self::assertSame(['craft-context-get'], array_column(json_decode((string)$response->getBody(), true)['result']['tools'], 'name'));
    }

    public function testAdminSessionsCannotCrossEndpointUserOrScopeBoundaries(): void
    {
        $user = User::find()->admin()->one();
        Craft::$app->getUser()->setIdentity($user);
        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_READ, Mcp::SCOPE_WRITE];
        $response = $this->exchange('admin', $this->initialize('2025-11-25'));
        self::assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        $headers = ['Mcp-Session-Id' => $response->getHeaderLine('Mcp-Session-Id'), 'MCP-Protocol-Version' => '2025-11-25'];
        $list = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'];

        // Scope ordering does not create a different session.
        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_WRITE, Mcp::SCOPE_READ];
        self::assertSame(200, $this->exchange('admin', $list, $headers)->getStatusCode());
        self::assertSame(404, $this->exchange('public', $list, $headers)->getStatusCode());

        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_READ];
        self::assertSame(404, $this->exchange('admin', $list, $headers)->getStatusCode());

        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_READ, Mcp::SCOPE_WRITE];
        $otherUser = clone $user;
        $otherUser->id += 1000;
        Craft::$app->getUser()->setIdentity($otherUser);
        self::assertSame(404, $this->exchange('admin', $list, $headers)->getStatusCode());
    }

    private function initialize(string $version): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
            'protocolVersion' => $version,
            'capabilities' => (object)[],
            'clientInfo' => ['name' => 'Craft test', 'version' => '1.0'],
        ]];
    }

    private function exchange(string $endpoint, array $message, array $headers = [], string $method = 'POST'): ResponseInterface
    {
        // Recreate both the server and file cache, as separate PHP requests would.
        Craft::$app->set('cache', new FileCache(['cachePath' => $this->cachePath]));
        $container = Craft::$app->get('mcp')->getCapabilityContainer();
        $server = $container->get(ServerFactory::class)->$endpoint();
        $request = new ServerRequest($method, 'http://localhost/mcp', $headers + [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
        ], json_encode($message));

        return $server->run(new StreamableHttpTransport(
            request: $request,
            middleware: $container->get(HttpTransportMiddleware::class)->forRequest($request),
        ));
    }
}
