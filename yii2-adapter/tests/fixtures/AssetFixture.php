<?php
/**
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\elements\AssetFixture as BaseAssetFixture;

/**
 * Class AssetsFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AssetFixture extends BaseAssetFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/assets.php';

    /**
     * @inheritdoc
     */
    public $depends = [VolumesFixture::class, VolumesFolderFixture::class];
}
