<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\conditions\entries\SectionConditionRule;
use craft\elements\conditions\StatusConditionRule;
use craft\elements\conditions\TitleConditionRule;
use craft\elements\conditions\users\UserCondition;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fields\conditions\TextFieldConditionRule;
use craft\fields\Entries;
use craft\fields\PlainText;
use craft\mcp\ServerFactory;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\test\TestCase;
use Mcp\Server\Transport\StdioTransport;

/** Conditions are described and validated the same way in each place they’re configured. */
class ConditionsCapabilityTest extends TestCase
{
    private PlainText $inLayout;
    private PlainText $elsewhere;
    private EntryType $entryType;

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $fields = Craft::$app->getFields();
        $this->inLayout = new PlainText(['name' => 'MCP Subtitle', 'handle' => 'mcpSubtitle']);
        $this->elsewhere = new PlainText(['name' => 'MCP Elsewhere', 'handle' => 'mcpElsewhere']);
        $fields->saveField($this->inLayout);
        $fields->saveField($this->elsewhere);

        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $layout]);
        $tab->setElements([new CustomField($this->inLayout)]);
        $layout->setTabs([$tab]);
        $this->entryType = new EntryType(['name' => 'MCP Conditional', 'handle' => 'mcpConditional']);
        $this->entryType->setFieldLayout($layout);
        Craft::$app->getEntries()->saveEntryType($this->entryType);
    }

    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteEntryType($this->entryType);
        Craft::$app->getFields()->deleteField($this->inLayout);
        Craft::$app->getFields()->deleteField($this->elsewhere);
        Craft::$app->getUser()->setIdentity(null);
    }

    public function testRulesDependOnWhereTheConditionIsUsed(): void
    {
        $classes = fn(array $arguments) => array_column($this->structured('condition_schema', $arguments)['rules'], 'class');

        // Customize Sources leaves status to the index’s own status menu
        $sources = $classes(['context' => 'element-sources', 'elementType' => 'entries']);
        self::assertContains(SectionConditionRule::class, $sources);
        self::assertNotContains(StatusConditionRule::class, $sources);

        // Element rules only offer the layout’s own fields
        $layout = $this->structured('condition_schema', ['context' => 'field-layout', 'fieldLayout' => $this->entryType->getFieldLayout()->uid]);
        $fieldUids = array_filter(array_map(fn(array $rule) => $rule['template']['fieldUid'] ?? null, $layout['rules']));
        self::assertSame([$this->inLayout->uid], array_values($fieldUids));
        self::assertSame(UserCondition::class, $this->structured('condition_schema', [
            'context' => 'field-layout', 'fieldLayout' => $this->entryType->getFieldLayout()->uid, 'condition' => 'user',
        ])['conditionClass']);

        // Entries fields choose sections with their sources setting, not the condition
        $selection = $classes(['context' => 'relation-selection', 'fieldType' => Entries::class]);
        self::assertContains(TitleConditionRule::class, $selection);
        self::assertNotContains(SectionConditionRule::class, $selection);
    }

    public function testFieldLayoutConditionsAreValidatedAndSaved(): void
    {
        $element = fn(array $condition) => ['fieldLayout' => ['tabs' => [['name' => 'Content', 'elements' => [
            ['type' => CustomField::class, 'fieldUid' => $this->inLayout->uid, 'elementCondition' => ['conditionRules' => [$condition]]],
        ]]]]];
        $update = fn(array $attributes) => $this->callTool('configuration_update', [
            'type' => 'entry-types',
            'identifier' => ['handle' => 'mcpConditional'],
            'attributes' => $attributes,
        ]);

        // Craft drops rules it can’t use without an error, so these must fail instead of saving an empty condition
        foreach ([
            'field outside the layout' => [['class' => TextFieldConditionRule::class, 'fieldUid' => $this->elsewhere->uid, 'operator' => '=', 'value' => 'x'], 'not included in the available field layouts'],
            'unknown setting' => [['class' => TitleConditionRule::class, 'operator' => '=', 'value' => 'x', 'text' => 'x'], 'text'],
        ] as $case => [$rule, $message]) {
            $result = $update($element($rule));
            self::assertTrue($result['isError'] ?? false, "$case was accepted.");
            self::assertStringContainsString($message, $result['content'][0]['text'], $case);
        }

        $saved = $update($element(['class' => TextFieldConditionRule::class, 'fieldUid' => $this->inLayout->uid, 'operator' => '=', 'value' => 'x']));
        self::assertFalse($saved['isError'] ?? false, json_encode($saved));
        $rules = $saved['structuredContent']['item']['fieldLayout']['config']['tabs'][0]['elements'][0]['elementCondition']['conditionRules'];
        self::assertSame([TextFieldConditionRule::class, 'x'], [$rules[0]['class'], $rules[0]['value']]);

        // The layout as reads return it can be sent back as is
        $resaved = $update(['fieldLayout' => $saved['structuredContent']['item']['fieldLayout']]);
        self::assertFalse($resaved['isError'] ?? false, json_encode($resaved));
        self::assertSame($rules, $resaved['structuredContent']['item']['fieldLayout']['config']['tabs'][0]['elements'][0]['elementCondition']['conditionRules']);
    }

    public function testRelationSelectionConditionsAreValidatedAndSaved(): void
    {
        $create = fn(array $rule) => $this->callTool('configuration_create', ['type' => 'fields', 'attributes' => [
            'type' => Entries::class,
            'name' => 'MCP Related',
            'handle' => 'mcpRelated',
            'settings' => ['selectionCondition' => ['conditionRules' => [$rule]]],
        ]]);

        $rejected = $create(['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => []]);
        self::assertTrue($rejected['isError'] ?? false, 'A section rule was accepted.');
        self::assertStringContainsString('settings.selectionCondition.conditionRules[0]', $rejected['content'][0]['text']);

        $saved = $create(['class' => TitleConditionRule::class, 'operator' => '**', 'value' => 'news']);
        self::assertFalse($saved['isError'] ?? false, json_encode($saved));

        try {
            // Sending the condition back as read keeps it
            $read = $saved['structuredContent']['item']['settings']['selectionCondition'];
            $rules = $read['conditionRules'];
            self::assertSame([TitleConditionRule::class, '**', 'news'], [$rules[0]['class'], $rules[0]['operator'], $rules[0]['value']]);

            $updated = $this->callTool('configuration_update', [
                'type' => 'fields',
                'identifier' => ['handle' => 'mcpRelated'],
                'attributes' => ['settings' => ['selectionCondition' => $read]],
            ]);
            self::assertFalse($updated['isError'] ?? false, json_encode($updated));
            $field = Craft::$app->getFields()->getFieldByHandle('mcpRelated');
            self::assertInstanceOf(Entries::class, $field);
            self::assertCount(1, $field->getSelectionCondition()?->getConditionRules() ?? []);
        } finally {
            Craft::$app->getFields()->deleteField(Craft::$app->getFields()->getFieldByHandle('mcpRelated'));
        }
    }

    private function structured(string $name, array $arguments): array
    {
        $result = $this->callTool($name, $arguments);
        self::assertFalse($result['isError'] ?? false, json_encode($result));

        return $result['structuredContent'];
    }

    private function callTool(string $name, array $arguments): array
    {
        $input = tmpfile();
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'conditions-capability-test', 'version' => '1.0'],
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
