<?php

namespace crafttests\unit\mcp;

use Closure;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\fields\PlainText;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\mcp\ServerFactory;
use craft\models\Site;
use craft\test\TestCase;
use Mcp\Server\Transport\StdioTransport;

class StdioTransportTest extends TestCase
{
    private mixed $originalIdentity;

    protected function _before(): void
    {
        $this->originalIdentity = Craft::$app->getUser()->getIdentity();
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity($this->originalIdentity);
    }

    public function testProjectConfigChangesAreSavedBeforeTheServerStops(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());

        $input = tmpfile();
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'create', 'method' => 'tools/call', 'params' => [
                'name' => 'configuration_create',
                'arguments' => ['type' => 'site-groups', 'attributes' => ['name' => 'MCP Stdio Archive']],
            ]],
        ] as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);
        $outputPath = tempnam(sys_get_temp_dir(), 'craft-mcp-stdio-');
        $output = fopen($outputPath, 'w');

        Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, $output));

        $response = file_get_contents($outputPath);
        unlink($outputPath);
        self::assertStringContainsString('"id":"create"', $response);
        self::assertStringNotContainsString('"isError":true', $response);

        // The console request hasn't ended, so only the stdio server could have saved it
        self::assertTrue((new Query())
            ->from(Table::PROJECTCONFIG)
            ->where(['like', 'path', 'siteGroups.%.name', false])
            ->andWhere(['value' => Json::encode('MCP Stdio Archive')])
            ->exists());
    }

    public function testProjectConfigChangesFromOtherProcessesAreSeenBetweenMessages(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $field = new PlainText(['name' => 'MCP Tagline', 'handle' => 'mcpTagline']);
        Craft::$app->getFields()->saveField($field);
        Craft::$app->getProjectConfig()->flush();
        $uid = $field->uid;

        $call = static fn(string $id, string $tool, array $arguments): array => ['jsonrpc' => '2.0', 'id' => $id, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $arguments]];
        $identifier = ['type' => 'fields', 'identifier' => ['handle' => 'mcpTagline']];
        $input = tmpfile();
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            $call('read', 'configuration_get', $identifier),
            $call('get', 'configuration_get', $identifier),
            $call('update', 'configuration_update', [...$identifier, 'attributes' => ['name' => 'MCP Tagline 2']]),
        ] as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);

        // Change the field the way another process, such as `craft project-config/set`, would
        StdioTestOutput::reset(['read' => function() use ($uid): void {
            $db = Craft::$app->getDb();
            $db->createCommand()->update(Table::PROJECTCONFIG, ['value' => Json::encode('Changed elsewhere')], ['path' => "fields.$uid.instructions"])->execute();
            $db->createCommand()->update(Table::FIELDS, ['instructions' => 'Changed elsewhere'], ['uid' => $uid])->execute();
            $db->createCommand()->update(Table::INFO, ['configVersion' => StringHelper::randomString(12)])->execute();
        }]);
        stream_wrapper_register('stdio-test-output', StdioTestOutput::class);

        try {
            Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, fopen('stdio-test-output://', 'w')));
        } finally {
            stream_wrapper_unregister('stdio-test-output');
        }

        $responses = StdioTestOutput::$responses;
        self::assertSame('Changed elsewhere', $responses['get']['result']['structuredContent']['item']['instructions']);
        self::assertNotSame($responses['read']['result']['structuredContent']['revision'], $responses['get']['result']['structuredContent']['revision']);
        self::assertFalse($responses['update']['result']['isError']);
        self::assertEquals(
            ["fields.$uid.instructions" => 'Changed elsewhere', "fields.$uid.name" => 'MCP Tagline 2'],
            array_map([Json::class, 'decode'], (new Query())
                ->select(['value', 'path'])
                ->from(Table::PROJECTCONFIG)
                ->where(['path' => ["fields.$uid.name", "fields.$uid.instructions"]])
                ->orderBy(['path' => SORT_ASC])
                ->indexBy('path')
                ->column()),
        );
    }

    public function testPrimarySiteChangesFromOtherProcessesAreSeenBetweenMessages(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $site = new Site([
            'groupId' => Craft::$app->getSites()->getPrimarySite()->groupId,
            'name' => 'MCP Second',
            'handle' => 'mcpSecond',
            'language' => 'en-US',
            'hasUrls' => true,
            'baseUrl' => 'https://second.test/',
        ]);
        Craft::$app->getSites()->saveSite($site);
        Craft::$app->getRoutes()->saveRoute(['mcp-second'], 'second', $site->uid);
        Craft::$app->getProjectConfig()->flush();

        $input = tmpfile();
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'before', 'method' => 'tools/call', 'params' => ['name' => 'configuration_list', 'arguments' => ['type' => 'routes']]],
            ['jsonrpc' => '2.0', 'id' => 'after', 'method' => 'tools/call', 'params' => ['name' => 'configuration_list', 'arguments' => ['type' => 'routes']]],
        ] as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);

        // Make the new site primary the way another process would
        StdioTestOutput::reset(['before' => function() use ($site): void {
            $db = Craft::$app->getDb();
            $db->createCommand()->update(Table::SITES, ['primary' => false])->execute();
            $db->createCommand()->update(Table::SITES, ['primary' => true], ['id' => $site->id])->execute();
            $db->createCommand()->update(Table::INFO, ['configVersion' => StringHelper::randomString(12)])->execute();
        }]);
        stream_wrapper_register('stdio-test-output', StdioTestOutput::class);

        try {
            Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, fopen('stdio-test-output://', 'w')));
        } finally {
            stream_wrapper_unregister('stdio-test-output');
        }

        $uris = fn(string $id): array => array_column(StdioTestOutput::$responses[$id]['result']['structuredContent']['items'], 'uri');
        self::assertNotContains('mcp-second', $uris('before'));
        self::assertContains('mcp-second', $uris('after'));
    }
}

/** Records each response as it's written, then runs the callback registered for it, before the next message is read. */
class StdioTestOutput
{
    /** @var array<string, array<string, mixed>> */
    public static array $responses = [];

    /** @var array<string, Closure> */
    public static array $after = [];

    /** @var resource|null */
    public $context;

    private string $buffer = '';

    /**
     * @param array<string, Closure> $after Callbacks to run after the response with the given ID is written
     */
    public static function reset(array $after): void
    {
        self::$responses = [];
        self::$after = $after;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        $this->buffer .= $data;

        while (($end = strpos($this->buffer, "\n")) !== false) {
            $response = Json::decode(substr($this->buffer, 0, $end));
            $this->buffer = substr($this->buffer, $end + 1);
            $id = $response['id'] ?? '';
            self::$responses[$id] = $response;
            (self::$after[$id] ?? null)?->__invoke();
        }

        return strlen($data);
    }
}
