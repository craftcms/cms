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
use craft\mcp\McpActor;
use craft\mcp\McpOutputContext;
use craft\mcp\serializers\ElementSerializer;
use craft\models\FieldLayout;
use craft\services\Config;
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
        return new ElementSerializer(new McpActor());
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
