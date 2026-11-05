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
use craft\mcp\ServerFactory;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use Mcp\Server\Transport\StdioTransport;

/** Uploads to Assets fields with selection conditions are staged in the user’s temporary folder before being moved. */
class AssetFieldUploadTest extends TestCase
{
    private string $file;
    private array $fields = [];
    private ?array $originalVolume = null;

    public function _fixtures(): array
    {
        return ['assets' => ['class' => AssetFixture::class]];
    }

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $this->file = sys_get_temp_dir() . '/photo.png';
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

        @unlink($this->file);
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
