<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\mcp\capabilities\AssetFolders;
use craft\models\VolumeFolder;
use craft\test\TestCase;
use crafttests\fixtures\VolumesFolderFixture;

class AssetFoldersCapabilityTest extends TestCase
{
    private mixed $originalIdentity;

    public function _fixtures(): array
    {
        return ['folders' => ['class' => VolumesFolderFixture::class]];
    }

    protected function _before(): void
    {
        $this->originalIdentity = Craft::$app->getUser()->getIdentity();
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity($this->originalIdentity);
    }

    public function testListFiltersToRootFoldersForNullParentId(): void
    {
        Craft::$app->getAssets()->storeFolderRecord(new VolumeFolder([
            'name' => 'Subfolder',
            'parentId' => 1000,
            'volumeId' => 1000,
            'path' => 'test volume 1/subfolder/',
        ]));

        $folders = Craft::$app->get('mcp')->getCapabilityContainer()->get(AssetFolders::class)->list(['parentId' => null])['folders'];

        self::assertEqualsCanonicalizing([1000, 1001], array_column($folders, 'id'));
    }
}
