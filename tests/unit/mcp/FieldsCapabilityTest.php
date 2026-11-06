<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\fields\Matrix;
use craft\mcp\ServerFactory;
use craft\models\EntryType;
use craft\test\TestCase;
use Mcp\Server\Transport\StdioTransport;

/** Exercises the fields configuration tools through the stdio MCP server. */
class FieldsCapabilityTest extends TestCase
{
    private EntryType $entryType;
    private Matrix $field;

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());

        $this->entryType = new EntryType(['name' => 'MCP Block', 'handle' => 'mcpBlock']);
        Craft::$app->getEntries()->saveEntryType($this->entryType);

        $this->field = new Matrix([
            'name' => 'MCP Matrix',
            'handle' => 'mcpMatrix',
            'propagationMethod' => PropagationMethod::Language,
        ]);
        $this->field->setEntryTypes([$this->entryType]);
        Craft::$app->getFields()->saveField($this->field);
    }

    protected function _after(): void
    {
        Craft::$app->getFields()->deleteField($this->field);
        Craft::$app->getEntries()->deleteEntryType($this->entryType);
        Craft::$app->getUser()->setIdentity(null);
    }

    public function testUpdatingMatrixSettingsKeepsEnumSettings(): void
    {
        $result = $this->callTool('configuration_update', [
            'type' => 'fields',
            'identifier' => ['handle' => 'mcpMatrix'],
            'attributes' => ['settings' => ['maxEntries' => 5]],
        ]);

        self::assertFalse($result['isError'] ?? false, json_encode($result));

        Craft::$app->getFields()->refreshFields();
        $field = Craft::$app->getFields()->getFieldByHandle('mcpMatrix');
        self::assertInstanceOf(Matrix::class, $field);
        self::assertSame(5, $field->maxEntries);
        self::assertSame(PropagationMethod::Language, $field->propagationMethod);
    }

    private function callTool(string $name, array $arguments): array
    {
        $input = fopen('php://memory', 'r+');
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'fields-capability-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'call', 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => $arguments]],
        ] as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);
        $outputPath = tempnam(sys_get_temp_dir(), 'craft-mcp-stdio-');

        Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, fopen($outputPath, 'w')));

        foreach (array_filter(explode("\n", (string)file_get_contents($outputPath))) as $line) {
            $response = json_decode($line, true);
            if (($response['id'] ?? null) === 'call') {
                unlink($outputPath);
                return $response['result'] ?? $response;
            }
        }

        self::fail("$name returned no response.");
    }
}
