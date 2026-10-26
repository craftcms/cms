<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Connection;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\mcp\contracts\ProvidesInputSchema;
use craft\mcp\schema\CustomFieldSchema;
use craft\models\FieldLayout;
use craft\services\Config;
use Mcp\Capability\Discovery\SchemaValidator;
use PHPUnit\Framework\TestCase;
use yii\console\Application;

class CustomFieldSchemaTest extends TestCase
{
    private mixed $originalApp;
    private array $originalAliases;
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalApp = Craft::$app;
        $this->originalAliases = Craft::$aliases;
        $this->originalTimezone = date_default_timezone_get();

        new SchemaTestApplication([
            'id' => 'mcp-field-schema-test',
            'basePath' => dirname(__DIR__, 3) . '/src',
            'components' => [
                'db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:'],
                'config' => ['class' => Config::class, 'configDir' => sys_get_temp_dir() . '/mcp-field-schema-test-config'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Craft::$app = $this->originalApp;
        Craft::$aliases = $this->originalAliases;
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    public function testGeneratedSchemaValidatesNativeAndPluginFieldInput(): void
    {
        $layoutFields = [
            $this->layoutField(new Email(['name' => 'Contact email', 'handle' => 'email']), required: true),
            $this->layoutField(new Dropdown(['name' => 'Priority', 'handle' => 'priority', 'options' => [['label' => 'Low', 'value' => 'low'], ['label' => 'High', 'value' => 'high']]])),
            $this->layoutField(new MultiSelect(['name' => 'Regions', 'handle' => 'regions', 'options' => [['label' => 'Europe', 'value' => 'eu']]])),
            $this->layoutField(new Number(['name' => 'Price', 'handle' => 'price'])),
            $this->layoutField(new SchemaPluginField(['name' => 'Coordinates', 'handle' => 'coordinates'])),
        ];
        $layout = new class($layoutFields) extends FieldLayout {
            public function __construct(private array $layoutFields)
            {
            }

            public function getEditableCustomFieldElements(ElementInterface $element): array
            {
                return $this->layoutFields;
            }
        };
        $element = new class($layout) extends Entry {
            public function __construct(private FieldLayout $layout)
            {
            }

            public function getFieldLayout(): ?FieldLayout
            {
                return $this->layout;
            }
        };
        $schema = (new CustomFieldSchema())->forElement($element);
        $validator = new SchemaValidator();
        $values = ['email' => 'editor@example.com', 'priority' => 'high', 'regions' => ['eu'], 'price' => 12.5, 'coordinates' => ['latitude' => 50.8]];

        self::assertSame([], $validator->validateAgainstJsonSchema($values, $schema));
        self::assertSame([], $validator->validateAgainstJsonSchema(['email' => 'editor@example.com', 'priority' => null, 'regions' => null, 'price' => null, 'coordinates' => null], $schema));

        foreach ([
            ['email' => null],
            ['priority' => 'urgent'],
            ['regions' => ['us']],
            ['price' => 'twelve'],
            ['coordinates' => ['latitude' => 95]],
            ['coordinates' => ['longitude' => 4.3]],
            ['unexpected' => true],
        ] as $invalid) {
            self::assertNotEmpty($validator->validateAgainstJsonSchema(array_replace($values, $invalid), $schema), json_encode($invalid));
        }

        unset($values['email']);
        self::assertNotEmpty($validator->validateAgainstJsonSchema($values, $schema));
    }

    private function layoutField(FieldInterface $field, bool $required = false): CustomField
    {
        return new CustomField($field, ['required' => $required]);
    }
}

class SchemaTestApplication extends Application
{
    public function getConfig(): Config
    {
        return $this->get('config');
    }
}

class SchemaPluginField extends PlainText implements ProvidesInputSchema
{
    public function getMcpInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['latitude' => ['type' => 'number', 'minimum' => -90, 'maximum' => 90]],
            'required' => ['latitude'],
            'additionalProperties' => false,
        ];
    }
}
