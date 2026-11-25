<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\models\VolumeFolder;
use craft\services\Mcp;
use craft\test\TestCase;
use crafttests\fixtures\VolumesFolderFixture;
use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Transport\StreamableHttpTransport;

/** Exercises the asset folder tools through the authenticated MCP server, so their input schemas apply. */
class AssetFoldersCapabilityTest extends TestCase
{
    private mixed $originalIdentity;

    public function _fixtures(): array
    {
        return ['folders' => ['class' => VolumesFolderFixture::class]];
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

    public function testListFiltersToRootFoldersForNullParentId(): void
    {
        Craft::$app->getAssets()->storeFolderRecord(new VolumeFolder([
            'name' => 'Subfolder',
            'parentId' => 1000,
            'volumeId' => 1000,
            'path' => 'test volume 1/subfolder/',
        ]));

        $result = $this->callTool('asset_folders_list', ['criteria' => ['parentId' => null]]);

        self::assertFalse($result['isError'] ?? true, json_encode($result));
        self::assertEqualsCanonicalizing([1000, 1001], array_column($result['structuredContent']['folders'], 'id'));
    }

    private function callTool(string $name, array $arguments): array
    {
        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_READ];
        $container = Craft::$app->get('mcp')->getCapabilityContainer();
        $request = new ServerRequest('POST', 'http://localhost/mcp', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'tools/call',
            'Mcp-Name' => $name,
        ], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => $name,
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
        $response = json_decode((string)$response->getBody(), true);

        return $response['result'] ?? $response;
    }
}
