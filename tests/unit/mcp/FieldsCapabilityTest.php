<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\mcp\SensitiveValues;
use craft\mcp\ServerFactory;
use craft\models\EntryType;
use craft\test\TestCase;
use Mcp\Server\Transport\StdioTransport;

/** Exercises the configuration tools through the stdio MCP server. */
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

    public function testReadingFieldsRedactsOnlySensitiveSettings(): void
    {
        $result = $this->callTool('configuration_get', [
            'type' => 'fields',
            'identifier' => ['handle' => 'mcpMatrix'],
        ]);

        self::assertFalse($result['isError'] ?? false, json_encode($result));
        $settings = $result['structuredContent']['item']['settings'];
        self::assertSame('language', $settings['propagationMethod']);
        self::assertSame('cards', $settings['viewMode']);

        $sensitiveValues = Craft::$app->get('mcp')->getCapabilityContainer()->get(SensitiveValues::class);
        self::assertSame(
            ['selectionLabel' => 'Choose', 'apiKey' => '•••••••'],
            $sensitiveValues->redact(['selectionLabel' => 'Choose', 'apiKey' => 'sk_live']),
        );
    }

    public function testOutdatedRevisionsAreRefusedWithoutChanges(): void
    {
        $fields = [];
        $layout = function(string $handle) use (&$fields): array {
            $field = $fields[] = new PlainText(['name' => $handle, 'handle' => $handle]);
            Craft::$app->getFields()->saveField($field);
            return ['tabs' => [['name' => 'Content', 'elements' => [['type' => CustomField::class, 'fieldUid' => $field->uid]]]]];
        };
        $layoutA = $layout('mcpFieldA');
        $layoutB = $layout('mcpFieldB');
        $identifier = ['handle' => 'mcpBlock'];

        try {
            $read = $this->callTool('configuration_get', ['type' => 'entry-types', 'identifier' => $identifier])['structuredContent'];
            $updated = $this->callTool('configuration_update', ['type' => 'entry-types', 'identifier' => $identifier, 'attributes' => ['fieldLayout' => $layoutA], 'expectedRevision' => $read['revision']]);
            self::assertFalse($updated['isError'] ?? false, json_encode($updated));

            foreach ([['configuration_update', ['attributes' => ['fieldLayout' => $layoutB]]], ['configuration_delete', []]] as [$tool, $arguments]) {
                $refused = $this->callTool($tool, ['type' => 'entry-types', 'identifier' => $identifier, ...$arguments, 'expectedRevision' => $read['revision']]);
                self::assertTrue($refused['isError'] ?? false, json_encode($refused));
                self::assertSame("The entry-types record has changed since revision {$read['revision']} was read, so nothing was changed. Read it again with configuration_get, redo your change on the current values, and pass the new revision.", $refused['content'][0]['text']);
            }

            Craft::$app->getProjectConfig()->reset();
            $current = $this->callTool('configuration_get', ['type' => 'entry-types', 'identifier' => $identifier])['structuredContent'];
            self::assertSame($updated['structuredContent']['revision'], $current['revision']);
            self::assertNotSame($read['revision'], $current['revision']);
            self::assertSame($layoutA['tabs'][0]['elements'][0]['fieldUid'], $current['item']['fieldLayout']['config']['tabs'][0]['elements'][0]['fieldUid']);

            $replaced = $this->callTool('configuration_update', ['type' => 'entry-types', 'identifier' => $identifier, 'attributes' => ['fieldLayout' => $layoutB], 'expectedRevision' => $current['revision']]);
            self::assertFalse($replaced['isError'] ?? false, json_encode($replaced));
            self::assertSame($layoutB['tabs'][0]['elements'][0]['fieldUid'], $replaced['structuredContent']['item']['fieldLayout']['config']['tabs'][0]['elements'][0]['fieldUid']);

            $deleted = $this->callTool('configuration_delete', ['type' => 'entry-types', 'identifier' => $identifier, 'expectedRevision' => $replaced['structuredContent']['revision']]);
            self::assertSame(['type' => 'entry-types', 'deleted' => true], $deleted['structuredContent'] ?? $deleted);
        } finally {
            foreach ($fields as $field) {
                Craft::$app->getFields()->deleteField($field);
            }
        }
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
