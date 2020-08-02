<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
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
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @todo https://github.com/robuust/craft-fixtures/blob/master/src/base/AssetFixture.php#L60 ? Why override?
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
abstract class AssetFixture extends ElementFixture
{
    /**
     * @inheritdoc
     */
    public $modelClass = Asset::class;

    /**
     * @var array
     */
    protected $volumeIds = [];

    /**
     * @var array
     */
    protected $folderIds = [];

    /**
     * @var array Used to track the files the fixture data file defines.
     */
    protected $files = [];

    /**
     * @var string
     */
    protected $sourceAssetPath;

    /**
     * @var string
     */
    protected $destinationAssetPath;

    /**
     * @inheritdoc
     */
    public function init()
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
     * Get asset model.
     *
     * @param array $data
     * @return ElementInterface
     */
    public function getElement(array $data = null)
    {
        /* @var Asset $element */
        $element = parent::getElement($data);

        if ($data === null) {
            $element->avoidFilenameConflicts = true;
            $element->setScenario(Asset::SCENARIO_REPLACE);
        }

        return $element;
    }

    /**
     * @inheritdoc
     */
    public function beforeLoad()
    {
        parent::beforeLoad();

        foreach ($this->files as $key => $fileInfo) {
            copy($this->sourceAssetPath . $fileInfo['filename'], $this->destinationAssetPath . $fileInfo['filename']);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeUnload()
    {
        parent::beforeUnload();

        FileHelper::clearDirectory($this->destinationAssetPath);
    }

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['volumeId', 'folderId', 'filename', 'title']);
    }
}
