<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Json;
use craft\mcp\ServerFactory;
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

        $input = fopen('php://memory', 'r+');
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'create', 'method' => 'tools/call', 'params' => [
                'name' => 'configuration.create',
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
}
