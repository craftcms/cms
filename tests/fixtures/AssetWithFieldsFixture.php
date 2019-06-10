<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\test\fixtures\elements\AssetFixture as BaseAssetFixture;

/**
 * Class AssetWithFieldsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AssetWithFieldsFixture extends BaseAssetFixture
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/asset-with-fields.php';

    /**
     * @inheritdoc
     */
    public $depends = [VolumesFixture::class, VolumesFolderFixture::class, FieldLayoutFixture::class];
}
