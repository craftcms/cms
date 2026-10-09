<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\records\Volume;
use craft\test\ActiveFixture;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Yii2Adapter\Filesystem\LegacyFilesystems;

/**
 * Class VolumesFixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class VolumesFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $modelClass = Volume::class;

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/volumes.php';

    /**
     * @inheritdoc
     */
    public $depends = [FieldLayoutFixture::class, FsFixture::class];

    public function load(): void
    {
        parent::load();

        Volumes::reset();
        app(LegacyFilesystems::class)->syncDisks();
    }

    public function unload(): void
    {
        parent::unload();

        Volumes::reset();
        app(LegacyFilesystems::class)->syncDisks();
    }
}
