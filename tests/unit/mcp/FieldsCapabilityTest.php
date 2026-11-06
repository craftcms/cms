<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\mcp\SensitiveValues;
use craft\mcp\ServerFactory;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
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

    public function testRejectsNonexistentSectionSourcesAndLayoutFieldsBeforeSaving(): void
    {
        $missing = '00000000-0000-4000-8000-000000000000';
        $news = $this->createSection('mcpNews');
        $sections = [$news];
        $fieldsService = Craft::$app->getFields();
        $entries = fn(array $sources, string $handle = 'mcpRelated') => [
            'type' => 'fields',
            'attributes' => ['type' => Entries::class, 'name' => $handle, 'handle' => $handle, 'settings' => ['sources' => $sources]],
        ];

        try {
            $this->assertToolError(
                $this->callTool('configuration_create', $entries(['*', "section:$missing"])),
                "settings.sources[1]: Source “section:{$missing}” doesn’t exist.",
            );
            self::assertFalse((new Query())->from(Table::FIELDS)->where(['handle' => 'mcpRelated'])->exists());

            $created = $this->callTool('configuration_create', $entries(["section:$news->uid"]));
            self::assertFalse($created['isError'] ?? false, json_encode($created));
            $field = $created['structuredContent']['item'];
            $wildcard = $this->callTool('configuration_create', $entries(['*'], 'mcpAnything'));
            self::assertFalse($wildcard['isError'] ?? false, json_encode($wildcard));

            // Sources were memoized by the earlier calls in this process
            $later = $sections[] = $this->createSection('mcpLater');
            $withLater = $this->callTool('configuration_update', [
                'type' => 'fields',
                'identifier' => ['id' => $field['id']],
                'attributes' => ['settings' => ['sources' => ["section:$news->uid", "section:$later->uid"]]],
            ]);
            self::assertFalse($withLater['isError'] ?? false, json_encode($withLater));

            $this->assertToolError($this->callTool('configuration_update', [
                'type' => 'fields',
                'identifier' => ['id' => $field['id']],
                'attributes' => ['settings' => ['sources' => ["section:$news->uid", 'section:mcpArchive']]],
            ]), 'section:mcpArchive');
            $fieldsService->refreshFields();
            $related = $fieldsService->getFieldById($field['id']);
            self::assertInstanceOf(Entries::class, $related);
            self::assertSame(["section:$news->uid", "section:$later->uid"], $related->sources);

            $this->assertToolError($this->callTool('configuration_update', [
                'type' => 'entry-types',
                'identifier' => ['uid' => $this->entryType->uid],
                'attributes' => ['fieldLayout' => ['tabs' => [['name' => 'Content', 'elements' => json_encode([
                    ['type' => CustomField::class, 'fieldUid' => $field['uid']],
                    ['type' => CustomField::class, 'fieldUid' => $missing],
                ])]]]],
            ]), "fieldLayout.tabs[0].elements[1].fieldUid: Field “{$missing}” doesn’t exist.");
            self::assertStringNotContainsString($missing, implode(',', (new Query())->select('config')->from(Table::FIELDLAYOUTS)->column()));
        } finally {
            foreach (['mcpRelated', 'mcpAnything'] as $handle) {
                if ($created = $fieldsService->getFieldByHandle($handle)) {
                    $fieldsService->deleteField($created);
                }
            }

            foreach ($sections as $section) {
                Craft::$app->getEntries()->deleteSection($section);
            }
        }
    }

    private function createSection(string $handle): Section
    {
        $section = new Section(['name' => $handle, 'handle' => $handle, 'type' => Section::TYPE_CHANNEL]);
        $section->setEntryTypes([$this->entryType]);
        $section->setSiteSettings([
            new Section_SiteSettings(['siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'hasUrls' => false]),
        ]);
        self::assertTrue(Craft::$app->getEntries()->saveSection($section), json_encode($section->getErrors()));

        return $section;
    }

    private function assertToolError(array $result, string $message): void
    {
        self::assertTrue($result['isError'] ?? false, json_encode($result));
        self::assertStringContainsString($message, $result['content'][0]['text']);
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
