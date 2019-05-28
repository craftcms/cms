<?php
/**
 *
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\fixtures;

use craft\helpers\FileHelper;
use craft\test\fixtures\elements\AssetFixture;

/**
 * Class AssetsFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class AssetsFixture extends AssetFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__.'/data/assets.php';

    /**
     * @inheritdoc
     */
    public $depends = [VolumesFixture::class, VolumesFolderFixture::class];

    // Private Properties
    // =========================================================================

    /**
     * @var array Used to track the files the fixture data file defines.
     */
    private $_files;

    /**
     * @var $string
     */
    private $_sourceAssetPath;

    /**
     * @var $string
     */
    private $_destinationAssetPath;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->_sourceAssetPath = dirname(__FILE__,2).'/_craft/assets/';
        $this->_destinationAssetPath = dirname(__FILE__,2).'/_craft/storage/runtime/temp/';

        $data = require $this->dataFile;

        foreach ($data as $fileName) {
            $this->_files[] = $fileName;
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeLoad()
    {
        parent::beforeLoad();

        foreach ($this->_files as $key => $fileInfo) {
            copy($this->_sourceAssetPath.$fileInfo['filename'], $this->_destinationAssetPath.$fileInfo['filename']);
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeUnload()
    {
        parent::beforeUnload();

        FileHelper::clearDirectory($this->_destinationAssetPath);
    }
}