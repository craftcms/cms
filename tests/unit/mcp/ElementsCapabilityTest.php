<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\services\Mcp;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Transport\StreamableHttpTransport;

/** Calls the elements_* tools through the MCP server, as clients do, so the SDK’s argument handling applies. */
class ElementsCapabilityTest extends TestCase
{
    private mixed $originalIdentity;

    public function _fixtures(): array
    {
        return ['entries' => ['class' => EntryFixture::class]];
    }

    protected function _before(): void
    {
        $this->originalIdentity = Craft::$app->getUser()->getIdentity();
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity($this->originalIdentity);
    }

    public function testListReturnsAllFieldsForNullFields(): void
    {
        // The SDK passes a parameter’s default for an explicit null, and elements_list defaults to metadata only
        $criteria = ['sectionId' => 1000, 'limit' => 1];

        self::assertArrayNotHasKey('plainTextField', $this->call('elements_list', ['type' => 'entries', 'criteria' => $criteria])['elements'][0]);
        self::assertSame('foo', $this->call('elements_list', ['type' => 'entries', 'criteria' => $criteria, 'fields' => null])['elements'][0]['plainTextField'] ?? null);
    }

    public function testListAcceptsOmittedCriteria(): void
    {
        self::assertNotEmpty($this->call('elements_list', ['type' => 'entries'])['elements']);
    }

    private function call(string $tool, array $arguments): array
    {
        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_READ];
        $container = Craft::$app->get('mcp')->getCapabilityContainer();
        $request = new ServerRequest('POST', 'http://localhost/mcp', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'tools/call',
            'Mcp-Name' => $tool,
        ], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => $tool,
            'arguments' => $arguments,
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object)[],
            ],
        ]]));
        $response = $container->get(ServerFactory::class)->authenticated()->run(new StreamableHttpTransport(
            request: $request,
            middleware: $container->get(HttpTransportMiddleware::class)->forRequest($request),
        ));
        $body = (string)$response->getBody();
        $result = json_decode($body, true)['result'] ?? null;
        self::assertIsArray($result, $body);
        self::assertFalse($result['isError'] ?? false, $body);

        return $result['structuredContent'] ?? json_decode($result['content'][0]['text'], true);
    }
}
