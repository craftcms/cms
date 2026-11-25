<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\Asset;
use craft\elements\User;
use craft\events\DefineAssetUrlEvent;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\services\Mcp;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Transport\StreamableHttpTransport;
use yii\base\Event;

class AssetTransformUrlTest extends TestCase
{
    private mixed $originalIdentity;

    public function _fixtures(): array
    {
        return ['assets' => ['class' => AssetFixture::class]];
    }

    protected function _before(): void
    {
        $this->originalIdentity = Craft::$app->getUser()->getIdentity();
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
    }

    protected function _after(): void
    {
        Event::off(Asset::class, Asset::EVENT_DEFINE_URL);
        Craft::$app->getUser()->setIdentity($this->originalIdentity);
    }

    public function testReturnsAbsoluteUrlsForVolumesWithRootRelativeUrls(): void
    {
        // Such as a generated transform in a volume whose URL is `/transforms`
        Event::on(Asset::class, Asset::EVENT_DEFINE_URL, function(DefineAssetUrlEvent $event) {
            $event->url = '/transforms/_100xAUTO/shinybrad.png';
        });
        $asset = Asset::find()->filename('shinybrad.png')->one();

        $result = $this->call('assets_transform_url', ['id' => $asset->id, 'transform' => ['width' => 100]]);

        self::assertSame(rtrim($asset->getSite()->getBaseUrl(), '/') . '/transforms/_100xAUTO/shinybrad.png', $result['url']);
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

        return $result['structuredContent'];
    }
}
