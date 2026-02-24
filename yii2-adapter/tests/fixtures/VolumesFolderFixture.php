<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\records\VolumeFolder;
use craft\test\ActiveFixture;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Support\Facades\Volumes;

/**
 * Class VolumeFolderFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class VolumesFolderFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $modelClass = VolumeFolder::class;

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/volumefolder.php';

    /**
     * @inheritdoc
     */
    public $depends = [VolumesFixture::class];

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        parent::load();

        Volumes::reset();
        app(Filesystems::class)->syncDisks();
    }
}
