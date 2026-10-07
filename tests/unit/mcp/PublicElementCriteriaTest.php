<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\db\Connection;
use craft\elements\Asset;
use craft\fields\PlainText;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\ElementQueryFactory;
use craft\mcp\public\Access;
use craft\mcp\public\ElementCriteria;
use craft\mcp\public\ElementType;
use craft\mcp\PublicElementTypes;
use craft\mcp\Settings;
use craft\models\FieldLayout;
use craft\models\Volume;
use craft\services\Config;
use craft\services\Elements;
use craft\services\Volumes;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use yii\console\Application;

class PublicElementCriteriaTest extends TestCase
{
    private PublicCriteriaTestApplication $app;
    private mixed $originalApp;
    private array $originalAliases;
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalApp = Craft::$app;
        $this->originalAliases = Craft::$aliases;
        $this->originalTimezone = date_default_timezone_get();

        $this->app = new PublicCriteriaTestApplication([
            'id' => 'public-criteria-test',
            'basePath' => dirname(__DIR__, 3) . '/src',
            'components' => [
                'db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:'],
                'config' => ['class' => Config::class, 'configDir' => sys_get_temp_dir() . '/mcp-public-criteria-test-config'],
                'volumes' => ['class' => PublicCriteriaTestVolumes::class],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Craft::$app = $this->originalApp;
        Craft::$aliases = $this->originalAliases;
        date_default_timezone_set($this->originalTimezone);
    }

    public function testCustomFieldCannotExposeNativeSqlConfiguration(): void
    {
        $layout = new class([ new PlainText(['name' => 'Selection', 'handle' => 'select']), new PlainText(['name' => 'Subtitle', 'handle' => 'subtitle']), ]) extends FieldLayout {
            public function __construct(private array $layoutFields)
            {
            }

            public function getCustomFields(): array
            {
                return $this->layoutFields;
            }
        };
        $volume = new Volume(['handle' => 'public', 'fieldLayout' => $layout]);
        $this->app->getVolumes()->volume = $volume;
        $settings = new Settings(['publicEnabled' => true, 'publicVolumeHandles' => ['public']]);
        $types = new PublicElementTypes(new ElementQueryFactory(new Elements(['bulkOpDb' => new Connection()])));
        $criteria = new ElementCriteria(new Access($settings, $types), new ElementQueryCriteria());
        $type = new ElementType('asset', Asset::class);

        $criteria->assertSupported($type, ['subtitle' => 'News']);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unsupported public query criterion [select]');
        $criteria->assertSupported($type, ['select' => '(SELECT password FROM users LIMIT 1) AS title']);
    }
}

class PublicCriteriaTestApplication extends Application
{
    public function getConfig(): Config
    {
        return $this->get('config');
    }

    public function getIsInstalled(): bool
    {
        return false;
    }

    public function getVolumes(): PublicCriteriaTestVolumes
    {
        return $this->get('volumes');
    }
}

class PublicCriteriaTestVolumes extends Volumes
{
    public Volume $volume;

    public function getVolumeByHandle(string $handle): ?Volume
    {
        return $handle === $this->volume->handle ? $this->volume : null;
    }
}
