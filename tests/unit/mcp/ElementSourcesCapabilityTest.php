<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\conditions\entries\PostDateConditionRule;
use craft\elements\conditions\entries\SectionConditionRule;
use craft\elements\conditions\HasUrlConditionRule;
use craft\elements\conditions\StatusConditionRule;
use craft\elements\Entry;
use craft\elements\User;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\services\Mcp;
use craft\services\ProjectConfig;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Transport\StreamableHttpTransport;

/** Calls element_sources_get and element_sources_save through the MCP server, as clients do. */
class ElementSourcesCapabilityTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'entries' => ['class' => EntryFixture::class],
        ];
    }

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
    }

    protected function _after(): void
    {
        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_ELEMENT_SOURCES . '.' . Entry::class);
        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_ELEMENT_SOURCE_PAGES . '.' . Entry::class);
        Craft::$app->getElementSources()->refreshSources();
        Craft::$app->getUser()->setIdentity(null);
    }

    public function testSavesTheWholeSidebarAndReadsItBack(): void
    {
        $read = $this->callTool('element_sources_get', ['elementType' => 'entries']);
        self::assertFalse($read['isError'] ?? false, json_encode($read));
        self::assertTrue($read['structuredContent']['multiPage']);

        $saved = $this->callTool('element_sources_save', [
            'elementType' => 'entries',
            'expectedRevision' => $read['structuredContent']['revision'],
            'pages' => [
                ['name' => 'Content', 'icon' => 'newspaper', 'sources' => [
                    ['type' => 'native', 'key' => '*', 'columns' => ['section', 'postDate'], 'defaultSort' => ['title', 'asc'], 'defaultViewMode' => 'cards'],
                    ['type' => 'heading', 'heading' => 'News'],
                    ['type' => 'custom', 'label' => 'Test 1 entries', 'columns' => [], 'condition' => ['conditionRules' => [
                        ['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => ['section-1000---------------------uid']],
                        // Rules without operators, as their templates list them
                        ['class' => HasUrlConditionRule::class, 'value' => true],
                        ['class' => PostDateConditionRule::class, 'rangeType' => 'past30Days', 'periodType' => 'daysAgo', 'periodValue' => null, 'startDate' => null, 'endDate' => null],
                    ]], 'userGroups' => []],
                ]],
                ['name' => 'Archive', 'icon' => 'box-archive', 'sources' => [
                    ['type' => 'native', 'key' => 'singles', 'enabled' => false],
                    ['type' => 'native', 'key' => 'section:section-1000---------------------uid'],
                ]],
            ],
        ]);
        self::assertFalse($saved['isError'] ?? false, json_encode($saved));
        $document = $saved['structuredContent'];

        self::assertSame(['Content', 'Archive'], array_column($document['pages'], 'name'));
        self::assertSame(['newspaper', 'box-archive'], array_column($document['pages'], 'icon'));
        [$all, $heading, $custom] = $document['pages'][0]['sources'];
        self::assertSame(['section', 'postDate'], $all['columns']);
        self::assertSame(['title', 'asc'], $all['defaultSort']);
        self::assertSame('cards', $all['defaultViewMode']);
        self::assertSame(['type' => 'heading', 'heading' => 'News'], ['type' => $heading['type'], 'heading' => $heading['heading']]);
        self::assertStringStartsWith('heading:', $heading['key']);
        self::assertStringStartsWith('custom:', $custom['key']);
        self::assertSame([], $custom['columns']);
        self::assertSame([], $custom['userGroups']);
        self::assertNull($custom['sites']);
        // The condition is stored in its normalized form, with the rule’s UID filled in
        self::assertSame(Entry::class, $custom['condition']['elementType']);
        self::assertSame(['section-1000---------------------uid'], $custom['condition']['conditionRules'][0]['values']);
        self::assertNotEmpty($custom['condition']['conditionRules'][0]['uid']);
        self::assertSame([true, 'past30Days'], [
            $custom['condition']['conditionRules'][1]['value'],
            $custom['condition']['conditionRules'][2]['rangeType'],
        ]);
        self::assertFalse($document['pages'][1]['sources'][0]['enabled']);
        self::assertTrue($document['customized']);

        // Native sources that were left out come back at the end of the first page, under a blank heading
        $added = array_column($document['addedNativeSources'], 'key');
        self::assertContains('section:section-1003---------------------uid', $added);
        self::assertNotContains('singles', $added);
        $firstPageKeys = array_map(fn(array $source) => $source['key'] ?? $source['heading'], $document['pages'][0]['sources']);
        self::assertSame('', $firstPageKeys[3]);
        self::assertSame($added, array_values(array_unique(array_slice($firstPageKeys, 4))));

        // Omitted settings keep their saved values; the custom source keeps its key
        $resaved = $this->callTool('element_sources_save', [
            'elementType' => 'entries',
            'expectedRevision' => $document['revision'],
            'pages' => [['name' => 'Content', 'sources' => [
                ['type' => 'native', 'key' => '*', 'defaultViewMode' => null],
                ['type' => 'custom', 'key' => $custom['key']],
            ]]],
        ]);
        self::assertFalse($resaved['isError'] ?? false, json_encode($resaved));
        [$all, $custom2] = $resaved['structuredContent']['pages'][0]['sources'];
        self::assertSame(['section', 'postDate'], $all['columns']);
        self::assertNull($all['defaultViewMode']);
        self::assertSame($custom['condition'], $custom2['condition']);
        self::assertSame('Test 1 entries', $custom2['label']);
        self::assertSame(['Content'], array_column($resaved['structuredContent']['pages'], 'name'));
        // An omitted icon keeps the page’s icon
        self::assertSame('newspaper', $resaved['structuredContent']['pages'][0]['icon']);

        $reset = $this->callTool('element_sources_save', ['elementType' => 'entries', 'reset' => true]);
        self::assertFalse($reset['isError'] ?? false, json_encode($reset));
        self::assertFalse($reset['structuredContent']['customized']);
        self::assertSame(['Entries'], array_column($reset['structuredContent']['pages'], 'name'));
        self::assertNull(Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ELEMENT_SOURCES . '.' . Entry::class));
    }

    public function testInvalidSidebarsAreRefusedWithoutChanges(): void
    {
        $revision = $this->callTool('element_sources_get', ['elementType' => 'entries'])['structuredContent']['revision'];
        $page = fn(array ...$sources) => ['pages' => [['name' => 'Entries', 'sources' => [['type' => 'native', 'key' => '*'], ...$sources]]]];
        $customSource = fn(array $rule) => ['type' => 'custom', 'label' => 'Probe', 'condition' => ['conditionRules' => [$rule]]];

        foreach ([
            'unknown column' => [$page(['type' => 'native', 'key' => 'singles', 'columns' => ['status', 'nope']]), 'nope'],
            'unknown sort' => [$page(['type' => 'native', 'key' => 'singles', 'defaultSort' => ['structure', 'asc']]), 'structure'],
            'unknown native source' => [$page(['type' => 'native', 'key' => 'section:missing']), 'section:missing'],
            'rule value outside options' => [$page($customSource(['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => ['missing-uid']])), 'missing-uid'],
            'unknown rule setting' => [$page($customSource(['class' => SectionConditionRule::class, 'operator' => 'in', 'sections' => []])), 'sections'],
            'operator on a rule without operators' => [$page($customSource(['class' => HasUrlConditionRule::class, 'operator' => '=', 'value' => true])), 'Omit operator'],
            'invalid operator' => [$page($customSource(['class' => SectionConditionRule::class, 'operator' => '=', 'values' => []])), 'operator'],
            'rule excluded from sources' => [$page($customSource(['class' => StatusConditionRule::class, 'values' => ['live']])), 'isn’t available'],
            'pages that only differ in case' => [['pages' => [
                ['name' => 'Entries', 'sources' => [['type' => 'native', 'key' => '*']]],
                ['name' => 'entries', 'sources' => [['type' => 'native', 'key' => 'singles']]],
            ]], 'same page'],
            'sources instead of pages' => [['sources' => [['type' => 'native', 'key' => '*']]], 'use pages'],
            'outdated revision' => [[...$page(), 'expectedRevision' => 'outdated'], 'has changed'],
        ] as $case => [$arguments, $message]) {
            $result = $this->callTool('element_sources_save', ['elementType' => 'entries', ...$arguments]);
            self::assertTrue($result['isError'] ?? false, "$case was accepted.");
            self::assertStringContainsString($message, $result['content'][0]['text'], $case);
        }

        self::assertSame($revision, $this->callTool('element_sources_get', ['elementType' => 'entries'])['structuredContent']['revision']);
        self::assertNull(Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ELEMENT_SOURCES . '.' . Entry::class));
    }

    private function callTool(string $name, array $arguments): array
    {
        Craft::$app->get('mcp')->tokenScopes = [Mcp::SCOPE_ADMIN];
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
        $body = (string)$response->getBody();
        $result = json_decode($body, true)['result'] ?? null;
        self::assertIsArray($result, $body);

        return $result;
    }
}
