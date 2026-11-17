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

        // `uploads/moved/` lives inside `Uploads/`, as it would on case-insensitive storage, while
        // `uploads/separate/` is a separate directory on case-sensitive storage
        $volume->createDirectory('Uploads/moved/nested');
        $volume->createDirectory('Uploads/photos/trip');
        $volume->createDirectory('Uploads/separate/inside');
        $volume->createDirectory('uploads/separate');
        $volume->createDirectory('Ⱥ/moved');

        $folder = $this->storeFolder(1000, 'Uploads/');
        $folderIds = [
            'moved' => $movedId = $this->storeFolder($folder, 'uploads/moved/'),
            'nested' => $this->storeFolder($movedId, 'uploads/moved/nested/'),
            'photos' => $photosId = $this->storeFolder($folder, 'Uploads/Photos/'),
            'trip' => $this->storeFolder($photosId, 'Uploads/photos/trip/'),
            'separate' => $separateId = $this->storeFolder($folder, 'uploads/separate/'),
            'inside' => $this->storeFolder($separateId, 'Uploads/separate/inside/'),
            'recordOnly' => $this->storeFolder($folder, 'Uploads/record-only/'),
            // `Ⱥ` and `ⱥ` differ in byte length
            'unicodeMoved' => $this->storeFolder($unicodeFolder = $this->storeFolder(1000, 'Ⱥ/'), 'ⱥ/moved/'),
        ];

        try {
            $assets->renameFolderById($folder, 'Renamed');
            $assets->renameFolderById($unicodeFolder, 'Unicode');

            $paths = (new Query())
                ->select(['path', 'id'])
                ->from(Table::VOLUMEFOLDERS)
                ->where(['id' => $folderIds])
                ->pairs();

            self::assertSame([
                'moved' => 'Renamed/moved/',
                'nested' => 'Renamed/moved/nested/',
                'photos' => 'Renamed/Photos/',
                'trip' => 'Renamed/photos/trip/',
                // On case-insensitive storage, `uploads/separate` was inside `Uploads` and moved along with it
                'separate' => $volume->directoryExists('uploads/separate') ? 'uploads/separate/' : 'Renamed/separate/',
                'inside' => 'Renamed/separate/inside/',
                'recordOnly' => 'Renamed/record-only/',
                'unicodeMoved' => 'Unicode/moved/',
            ], array_map(fn(int $id) => array_search($id, $paths), $folderIds));
        } finally {
            FileHelper::removeDirectory($fsPath);
        }
    }

    private function storeFolder(int $parentId, string $path): int
    {
        $folder = new VolumeFolder([
            'name' => basename($path),
            'parentId' => $parentId,
            'volumeId' => 1000,
            'path' => $path,
        ]);
        Craft::$app->getAssets()->storeFolderRecord($folder);
        return $folder->id;
    }
}
