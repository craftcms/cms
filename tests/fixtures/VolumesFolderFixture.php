<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\fixtures;

use Craft;
use craft\records\VolumeFolder;
use craft\services\Volumes;
use yii\test\ActiveFixture;

/**
 * Class VolumeFolderFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class VolumesFolderFixture extends ActiveFixture
{
    public $modelClass = VolumeFolder::class;
    public $dataFile = __DIR__.'/data/volumefolder.php';
    public $depends = [VolumesFixture::class];

    public function load()
    {
        parent::load();

        Craft::$app->set('volumes', new Volumes());
    }
}
