<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\records\VolumeFolder;

/**
 * Class AssetFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
abstract class AssetFixture extends BaseElementFixture
{
    /**
     * @var array
     */
    protected array $volumeIds = [];

    /**
     * @var array
     */
    protected array $folderIds = [];

    /**
     * @var array Used to track the files the fixture data file defines.
     */
    protected array $files = [];

    /**
     * @var string
     */
    protected string $sourceAssetPath;

    /**
     * @var string
     */
    protected string $destinationAssetPath;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $this->volumeIds[$volume->handle] = $volume->id;
            $this->folderIds[$volume->handle] = VolumeFolder::findOne([
                'parentId' => null,
                'name' => $volume->name,
                'volumeId' => $volume->id,
            ])->id;
        }

        $this->sourceAssetPath = Craft::$app->getPath()->getTestsPath() . '/_craft/assets/';
        $this->destinationAssetPath = Craft::$app->getPath()->getStoragePath() . '/runtime/temp/';

        if (!is_dir($this->destinationAssetPath)) {
            FileHelper::createDirectory($this->destinationAssetPath);
        }

        $data = require $this->dataFile;

        foreach ($data as $fileName) {
            $this->files[] = $fileName;
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeLoad(): void
    {
        parent::beforeLoad();

        foreach ($this->files as $fileInfo) {
            copy($this->sourceAssetPath . $fileInfo['filename'], $this->destinationAssetPath . $fileInfo['filename']);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeUnload(): void
    {
        parent::beforeUnload();

        FileHelper::clearDirectory($this->destinationAssetPath);
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new Asset();
    }
}
