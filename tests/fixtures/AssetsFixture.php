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
}