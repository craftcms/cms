<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\Asset;
use craft\elements\conditions\assets\AssetCondition;
use craft\elements\conditions\assets\FilenameConditionRule;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets as AssetsField;
use craft\fields\PlainText;
use craft\helpers\FileHelper;
use craft\mcp\AssetUploads;
use craft\mcp\ServerFactory;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use Mcp\Exception\ToolCallException;
use Mcp\Server\Transport\StdioTransport;

/** Exercises MCP asset uploads: local paths, upload URLs, and field uploads through the stdio MCP server. */
class AssetFieldUploadTest extends TestCase
{
    private string $file;
    private array $fields = [];
    private ?array $originalVolume = null;
    private mixed $originalMaxUploadFileSize;

    public function _fixtures(): array
    {
        return ['assets' => ['class' => AssetFixture::class]];
    }

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        // Other tests may leave a lower limit behind
        $this->originalMaxUploadFileSize = Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;
        Craft::$app->getConfig()->getGeneral()->maxUploadFileSize = 1024 * 1024;
        // Local paths must be within the project, outside system directories (which include tests/)
        FileHelper::createDirectory(Craft::getAlias('@root/mcp-upload-test'));
        $this->file = Craft::getAlias('@root/mcp-upload-test/photo.png');
        file_put_contents($this->file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    }

    protected function _after(): void
    {
        foreach (Asset::find()->folderPath('field-upload-test/*')->all() as $asset) {
            Craft::$app->getElements()->deleteElement($asset, true);
        }

        if ($this->originalVolume) {
            [$layout, $subpath] = $this->originalVolume;
            $volume = Craft::$app->getVolumes()->getVolumeById(1000);
            $volume->setFieldLayout($layout);
            $volume->setSubpath($subpath);
            Craft::$app->getVolumes()->saveVolume($volume);
        }

        foreach ($this->fields as $field) {
            Craft::$app->getFields()->deleteField($field);
        }

        FileHelper::removeDirectory(Craft::getAlias('@root/mcp-upload-test'));
        Craft::$app->getConfig()->getGeneral()->maxUploadFileSize = $this->originalMaxUploadFileSize;
        Craft::$app->getUser()->setIdentity(null);
    }

    public function testStagedUploadMatchesFinalFilenameAndKeepsFieldValues(): void
    {
        $caption = new PlainText(['name' => 'Caption', 'handle' => 'caption']);
        self::assertTrue(Craft::$app->getFields()->saveField($caption));
        $this->fields[] = $caption;
        $volume = Craft::$app->getVolumes()->getVolumeById(1000);
        $this->originalVolume = [$volume->getFieldLayout(), $volume->getSubpath(false, false)];
        $layout = new FieldLayout(['type' => Asset::class]);
        $tab = new FieldLayoutTab(['name' => 'Content', 'layout' => $layout]);
        $tab->setElements([new CustomField($caption)]);
        $layout->setTabs([$tab]);
        $volume->setFieldLayout($layout);
        $volume->setSubpath('volume-1000');
        self::assertTrue(Craft::$app->getVolumes()->saveVolume($volume));

        $rule = new FilenameConditionRule();
        $rule->value = 'photo.png';
        $condition = new AssetCondition(Asset::class);
        $condition->addConditionRule($rule);
        $field = new AssetsField([
            'name' => 'Photo',
            'handle' => 'photo',
            'defaultUploadLocationSource' => "volume:$volume->uid",
            'defaultUploadLocationSubpath' => 'field-upload-test',
        ]);
        $field->setSelectionCondition($condition->getConfig());
        self::assertTrue(Craft::$app->getFields()->saveField($field));
        $this->fields[] = $field;

        $result = $this->createAsset(['fieldId' => $field->id, 'fields' => ['caption' => 'Hello'], 'file' => ['path' => $this->file]]);
        self::assertArrayNotHasKey('isError', array_filter($result), json_encode($result));

        Craft::$app->getElements()->invalidateAllCaches();
        $asset = Asset::find()->id(json_decode($result['content'][0]['text'], true)['asset']['id'])->one();
        self::assertSame('photo.png', $asset->getFilename());
        self::assertSame('field-upload-test/', $asset->getFolder()->path);
        self::assertSame('Hello', $asset->getFieldValue('caption'));
    }

    public function testUploadUrlsRequirePermissionToSaveAssets(): void
    {
        $user = new User(['username' => 'mcp-uploader', 'email' => 'mcp-uploader@example.com', 'active' => true]);
        self::assertTrue(Craft::$app->getElements()->saveElement($user, false));
        $uploads = new AssetUploads();

        try {
            $uploads->createUpload($user, 'https://example.test/mcp');
            self::fail('Users without saveAssets permission must not get upload URLs.');
        } catch (ToolCallException $exception) {
            self::assertSame('You are not authorized to upload assets.', $exception->getMessage());
        }

        $volumeUid = Craft::$app->getVolumes()->getVolumeById(1000)->uid;
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ["viewassets:$volumeUid", "saveassets:$volumeUid"]);
        $user = User::find()->id($user->id)->status(null)->one();

        try {
            self::assertArrayHasKey('upload_url', $uploads->createUpload($user, 'https://example.test/mcp'));
        } finally {
            Craft::$app->getElements()->deleteElement($user, true);
        }
    }

    /** @dataProvider restrictedPaths */
    public function testRejectsPathsWhereVolumesCannotLive(string $path): void
    {
        $path = Craft::getAlias($path);
        copy($this->file, $path);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('within the Craft project, outside of system directories');

        try {
            (new AssetUploads())->consumeFile(['path' => $path], 'photo.png', static function(): void {
                self::fail('Restricted paths must not be read.');
            });
        } finally {
            unlink($path);
        }
    }

    public static function restrictedPaths(): array
    {
        return [
            'outside the project' => [sys_get_temp_dir() . '/mcp-upload-test.png'],
            'project root' => ['@root/mcp-upload-test.png'],
            'config directory' => ['@config/mcp-upload-test.png'],
        ];
    }

    private function createAsset(array $arguments): array
    {
        $input = fopen('php://memory', 'r+');
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'asset-field-upload-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'create', 'method' => 'tools/call', 'params' => ['name' => 'assets_create', 'arguments' => $arguments]],
        ] as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);
        $outputPath = tempnam(sys_get_temp_dir(), 'craft-mcp-stdio-');

        Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, fopen($outputPath, 'w')));

        foreach (array_filter(explode("\n", (string)file_get_contents($outputPath))) as $line) {
            $response = json_decode($line, true);
            if (($response['id'] ?? null) === 'create') {
                unlink($outputPath);
                return $response['result'] ?? $response;
            }
        }

        self::fail('assets_create returned no response.');
    }
}
