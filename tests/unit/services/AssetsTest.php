<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\fs\Local;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\models\VolumeFolder;
use craft\test\TestCase;
use crafttests\fixtures\VolumesFolderFixture;

class AssetsTest extends TestCase
{
    public function _fixtures(): array
    {
        return ['folders' => ['class' => VolumesFolderFixture::class]];
    }

    public function testRenameFolderUpdatesSubfoldersWithDifferentlyCasedPaths(): void
    {
        $assets = Craft::$app->getAssets();
        $volume = Craft::$app->getVolumes()->getVolumeById(1000);
        $fsPath = Craft::$app->getPath()->getTempPath() . '/assets-test-' . StringHelper::randomString(6);
        $volume->setFs(new Local(['handle' => 'assetsTest', 'path' => $fsPath]));
        $volume->createDirectory('Uploads');

        $folder = new VolumeFolder(['name' => 'Uploads', 'parentId' => 1000, 'volumeId' => 1000, 'path' => 'Uploads/']);
        $assets->storeFolderRecord($folder);
        $subfolder = new VolumeFolder(['name' => 'my-entry', 'parentId' => $folder->id, 'volumeId' => 1000, 'path' => 'uploads/my-entry/']);
        $assets->storeFolderRecord($subfolder);
        $nestedFolder = new VolumeFolder(['name' => 'nested', 'parentId' => $subfolder->id, 'volumeId' => 1000, 'path' => 'uploads/my-entry/nested/']);
        $assets->storeFolderRecord($nestedFolder);

        try {
            $assets->renameFolderById($folder->id, 'Renamed');

            $paths = (new Query())
                ->select(['path'])
                ->from(Table::VOLUMEFOLDERS)
                ->where(['id' => [$subfolder->id, $nestedFolder->id]])
                ->orderBy(['id' => SORT_ASC])
                ->column();
            self::assertSame(['Renamed/my-entry/', 'Renamed/my-entry/nested/'], $paths);
        } finally {
            FileHelper::removeDirectory($fsPath);
        }
    }

    public function testEnsureFolderUsesExistingFolderPathCase(): void
    {
        if (Craft::$app->getDb()->getIsPgsql()) {
            $this->markTestSkipped('Folder path lookups are case-sensitive on PostgreSQL.');
        }

        $assets = Craft::$app->getAssets();
        $folder = new VolumeFolder(['name' => 'Uploads', 'parentId' => 1000, 'volumeId' => 1000, 'path' => 'Uploads/']);
        $assets->storeFolderRecord($folder);

        $subfolder = $assets->ensureFolderByFullPathAndVolume('uploads/my-entry', Craft::$app->getVolumes()->getVolumeById(1000));

        self::assertSame($folder->id, $subfolder->parentId);
        self::assertSame('Uploads/my-entry/', $subfolder->path);
    }
}
