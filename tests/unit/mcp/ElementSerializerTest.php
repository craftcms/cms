<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\base\ElementInterface;
use craft\base\McpOutputFieldInterface;
use craft\db\Connection;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\fields\Lightswitch;
use craft\fields\PlainText;
use craft\mcp\elements\ElementAdapterRegistry;
use craft\mcp\McpActor;
use craft\mcp\McpOutputContext;
use craft\mcp\serializers\ElementSerializer;
use craft\models\FieldLayout;
use craft\services\Config;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use yii\console\Application;

class ElementSerializerTest extends TestCase
{
    private mixed $originalApp;
    private array $originalAliases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalApp = Craft::$app;
        $this->originalAliases = Craft::$aliases;

        new SerializerTestApplication([
            'id' => 'mcp-element-serializer-test',
            'basePath' => dirname(__DIR__, 3) . '/src',
            'components' => [
                'db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:'],
                'config' => ['class' => Config::class, 'configDir' => sys_get_temp_dir() . '/mcp-element-serializer-test-config'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Craft::$app = $this->originalApp;
        Craft::$aliases = $this->originalAliases;
        parent::tearDown();
    }

    public function testFieldsCanControlTheirMcpOutput(): void
    {
        $element = $this->element(
            [
                new Lightswitch(['handle' => 'toggle']),
                new McpOutputTestField(['handle' => 'custom']),
            ],
            ['toggle' => true, 'custom' => 'raw'],
        );

        $data = $this->serializer()->serialize($element, data: [], fields: null);

        self::assertTrue($data['toggle']);
        self::assertSame(['value' => 'raw', 'public' => false, 'requested' => false], $data['custom']);

        $data = $this->serializer()->serialize($element, data: [], public: true, fields: ['custom']);

        self::assertSame(['custom' => ['value' => 'raw', 'public' => true, 'requested' => true]], $data);
    }

    public function testElementsReturnedByFieldsAreExpandedAsNestedElements(): void
    {
        $element = $this->element(
            [new McpOutputTestField(['handle' => 'related'])],
            ['related' => ElementCollection::make([$this->nestedEntry(1), $this->nestedEntry(2)])],
        );
        $serializeNested = fn(ElementInterface $nested): ?array => $nested->id === 1 ? ['id' => $nested->id] : null;
        $serializer = $this->serializer();

        self::assertSame(
            ['related' => [['id' => 1]]],
            $serializer->serialize($element, data: [], fields: ['related'], serializeNested: $serializeNested),
        );
        self::assertSame([], $serializer->serialize($element, data: [], fields: null, serializeNested: $serializeNested));
    }

    public function testNestedFieldsOfNestedElementsAreOmittedByDefault(): void
    {
        $element = $this->tree();
        $serializer = $this->serializer();

        self::assertSame([], $serializer->serialize($element, data: [], fields: null, serializeNested: $this->visible($serializer)));
        self::assertSame(
            ['blocks' => [['id' => 10, 'heading' => 'Block']]],
            $serializer->serialize($element, data: [], fields: ['blocks'], serializeNested: $this->visible($serializer)),
        );
    }

    public function testDotPathsIncludeNestedFieldsAsSummaries(): void
    {
        $element = $this->tree();
        $serializer = $this->serializer();
        $expected = ['blocks' => [[
            'id' => 10,
            'heading' => 'Block',
            'inner' => [['type' => TestEntry::class, 'id' => 20, 'status' => 'live']],
        ]]];

        self::assertSame($expected, $serializer->serialize($element, data: [], fields: ['blocks', 'blocks.inner'], serializeNested: $this->visible($serializer)));
        self::assertSame($expected, $serializer->serialize($element, data: [], fields: ['blocks.inner'], serializeNested: $this->visible($serializer)));
    }

    public function testElementsThatCantBeFetchedAreExpandedInsteadOfSummarized(): void
    {
        $block = new UnfetchableTestEntry(30, [new Lightswitch(['handle' => 'text'])], ['text' => 'Hi']);
        $element = new TestEntry(1, [new McpOutputTestField(['handle' => 'blocks'])], [
            'blocks' => ElementCollection::make([new TestEntry(10, [new McpOutputTestField(['handle' => 'content'])], ['content' => ElementCollection::make([$block])])]),
        ]);
        $serializer = $this->serializer();

        self::assertSame(
            ['blocks' => [['id' => 10, 'content' => [['id' => 30, 'text' => 'Hi']]]]],
            $serializer->serialize($element, data: [], fields: ['blocks.content'], serializeNested: $this->visible($serializer)),
        );
    }

    public function testElementsWithinFieldValueArraysAreResolved(): void
    {
        $element = new TestEntry(1, [new McpOutputTestField(['handle' => 'custom'])], [
            'custom' => ['html' => '<p>Hi</p>', 'entries' => ElementCollection::make([new TestEntry(10, [], [])])],
        ]);
        $serializer = $this->serializer();

        self::assertSame(
            ['custom' => ['value' => ['html' => '<p>Hi</p>', 'entries' => [['id' => 10]]], 'public' => false, 'requested' => true]],
            $serializer->serialize($element, data: [], fields: ['custom'], serializeNested: $this->visible($serializer)),
        );
        self::assertSame(
            ['custom' => ['value' => ['html' => '<p>Hi</p>'], 'public' => false, 'requested' => false]],
            $serializer->serialize($element, data: [], fields: null, serializeNested: $this->visible($serializer)),
        );
    }

    public function testNestedElementsTheUserCantViewAreOmittedFromSummaries(): void
    {
        $serializer = $this->serializer();
        $serializeNested = function(ElementInterface $nested, array $paths = [], bool $summary = false) use ($serializer, &$serializeNested): ?array {
            if ($nested->getId() === 20) {
                return null;
            }

            return $summary
                ? $serializer->summarize($nested)
                : $serializer->serialize($nested, data: ['id' => $nested->getId()], serializeNested: $serializeNested, nestedPaths: $paths);
        };

        self::assertSame(
            ['blocks' => [['id' => 10, 'heading' => 'Block', 'inner' => []]]],
            $serializer->serialize($this->tree(), data: [], fields: ['blocks.inner'], serializeNested: $serializeNested),
        );
    }

    public function testFieldsNamedViaDotPathsAreRequested(): void
    {
        $block = new TestEntry(10, [new McpOutputTestField(['handle' => 'custom'])], ['custom' => 'raw']);
        $element = new TestEntry(1, [new McpOutputTestField(['handle' => 'blocks'])], ['blocks' => ElementCollection::make([$block])]);
        $serializer = $this->serializer();

        self::assertSame(
            ['blocks' => [['id' => 10, 'custom' => ['value' => 'raw', 'public' => false, 'requested' => false]]]],
            $serializer->serialize($element, data: [], fields: ['blocks'], serializeNested: $this->visible($serializer)),
        );
        self::assertSame(
            ['blocks' => [['id' => 10, 'custom' => ['value' => 'raw', 'public' => false, 'requested' => true]]]],
            $serializer->serialize($element, data: [], fields: ['blocks.custom'], serializeNested: $this->visible($serializer)),
        );
    }

    public function testListItemsWithHiddenElementsAreRemoved(): void
    {
        $element = new TestEntry(1, [new McpOutputTestField(['handle' => 'custom'])], [
            'custom' => [
                ['type' => 'markup', 'html' => '<p>A</p>'],
                ['type' => 'entry', 'entry' => new TestEntry(20, [], [])],
                ['type' => 'entry', 'entry' => new TestEntry(10, [], [])],
                ['marker', new TestEntry(20, [], [])],
                ['marker', new TestEntry(10, [], [])],
                [new TestEntry(10, [], []), new TestEntry(20, [], [])],
            ],
        ]);
        $serializer = $this->serializer();
        $serializeNested = fn(ElementInterface $nested): ?array => $nested->getId() === 20 ? null : ['id' => $nested->getId()];

        self::assertSame(
            ['custom' => ['value' => [
                ['type' => 'markup', 'html' => '<p>A</p>'],
                ['type' => 'entry', 'entry' => ['id' => 10]],
                ['marker', ['id' => 10]],
                [['id' => 10]],
            ], 'public' => false, 'requested' => true]],
            $serializer->serialize($element, data: [], fields: ['custom'], serializeNested: $serializeNested),
        );

        // Hidden elements are removed the same way when nested elements are omitted
        self::assertSame(
            ['custom' => ['value' => [
                ['type' => 'markup', 'html' => '<p>A</p>'],
                ['type' => 'entry'],
                ['marker'],
                [],
            ], 'public' => false, 'requested' => false]],
            $serializer->serialize($element, data: [], fields: null, serializeNested: $serializeNested),
        );
    }

    public function testNestedElementLimitIsSharedAcrossLevels(): void
    {
        $blocks = array_map(
            fn(int $i): TestEntry => new TestEntry(100 + $i, [new McpOutputTestField(['handle' => 'inner'])], [
                'inner' => ElementCollection::make([new TestEntry(200 + $i, [], [])]),
            ]),
            range(1, 60),
        );
        $element = new TestEntry(1, [new McpOutputTestField(['handle' => 'blocks'])], ['blocks' => ElementCollection::make($blocks)]);
        $serializer = $this->serializer();

        $serializer->serialize($element, data: [], fields: ['blocks'], serializeNested: $this->visible($serializer));

        $this->expectException(ToolCallException::class);
        $serializer->serialize($element, data: [], fields: ['blocks.inner'], serializeNested: $this->visible($serializer));
    }

    public function testNestedElementLimitIsSharedWithNativeNestedFields(): void
    {
        $element = new TestEntry(1, [new McpOutputTestField(['handle' => 'blocks'])], [
            'blocks' => ElementCollection::make(array_map(fn(int $i): TestEntry => new TestEntry(100 + $i, [], []), range(1, 80))),
        ]);
        $native = ElementCollection::make(array_map(fn(int $i): TestEntry => new TestEntry(200 + $i, [], []), range(1, 30)));
        $serializer = $this->serializer();

        $serializer->serialize($element, data: [], fields: ['blocks'], serializeNested: $this->visible($serializer));
        $serializer->serializeNestedValue($native, serializeNested: $this->visible($serializer));

        $this->expectException(ToolCallException::class);
        $serializer->withBudget(function() use ($serializer, $element, $native): void {
            $serializer->serialize($element, data: [], fields: ['blocks'], serializeNested: $this->visible($serializer));
            $serializer->serializeNestedValue($native, serializeNested: $this->visible($serializer));
        });
    }

    /**
     * Returns an entry with a nested `blocks` field, whose entry has a nested `inner` field.
     */
    private function tree(): TestEntry
    {
        $inner = new TestEntry(20, [], []);
        $block = new TestEntry(10, [
            new Lightswitch(['handle' => 'heading']),
            new McpOutputTestField(['handle' => 'inner']),
        ], [
            'heading' => 'Block',
            'inner' => ElementCollection::make([$inner]),
        ]);

        return new TestEntry(1, [new McpOutputTestField(['handle' => 'blocks'])], ['blocks' => ElementCollection::make([$block])]);
    }

    /**
     * Returns a nested element serializer that treats every element as viewable.
     */
    private function visible(ElementSerializer $serializer): \Closure
    {
        $serializeNested = function(ElementInterface $nested, array $paths = [], bool $summary = false) use ($serializer, &$serializeNested): array {
            return $summary
                ? $serializer->summarize($nested)
                : $serializer->serialize($nested, data: ['id' => $nested->getId()], serializeNested: $serializeNested, nestedPaths: $paths);
        };

        return $serializeNested;
    }

    private function nestedEntry(int $id): Entry
    {
        return new class($id) extends Entry {
            public function __construct(int $id)
            {
                $this->id = $id;
            }
        };
    }

    private function serializer(): ElementSerializer
    {
        $adapters = new class() extends ElementAdapterRegistry {
            public function supports(string $elementType): bool
            {
                return !is_a($elementType, UnfetchableTestEntry::class, true);
            }
        };

        return new ElementSerializer(new McpActor(), adapters: $adapters);
    }

    private function element(array $fields, array $values): Entry
    {
        $layout = new class($fields) extends FieldLayout {
            public function __construct(private array $layoutFields)
            {
            }

            public function getCustomFields(): array
            {
                return $this->layoutFields;
            }

            public function getVisibleCustomFields(ElementInterface $element): array
            {
                return $this->layoutFields;
            }
        };

        return new class($layout, $values) extends Entry {
            public function __construct(private FieldLayout $layout, private array $values)
            {
            }

            public function getFieldLayout(): FieldLayout
            {
                return $this->layout;
            }

            public function getFieldValue(string $fieldHandle): mixed
            {
                return $this->values[$fieldHandle] ?? null;
            }
        };
    }
}

class SerializerTestApplication extends Application
{
    public function getConfig(): Config
    {
        return $this->get('config');
    }
}

class McpOutputTestField extends PlainText implements McpOutputFieldInterface
{
    public function getMcpValue(mixed $value, ElementInterface $element, McpOutputContext $context): mixed
    {
        return $value instanceof ElementCollection ? $value : ['value' => $value, 'public' => $context->public, 'requested' => $context->requested];
    }
}

class TestEntry extends Entry
{
    private FieldLayout $testLayout;

    public function __construct(int $id, private array $testFields, private array $testValues)
    {
        $this->id = $id;
        $this->testLayout = new class($testFields) extends FieldLayout {
            public function __construct(private array $layoutFields)
            {
            }

            public function getCustomFields(): array
            {
                return $this->layoutFields;
            }

            public function getVisibleCustomFields(ElementInterface $element): array
            {
                return $this->layoutFields;
            }
        };
    }

    public function getFieldLayout(): FieldLayout
    {
        return $this->testLayout;
    }

    public function getFieldValue(string $fieldHandle): mixed
    {
        return $this->testValues[$fieldHandle] ?? null;
    }

    public function getStatus(): ?string
    {
        return 'live';
    }
}

class UnfetchableTestEntry extends TestEntry
{
}
