<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\records\VolumeFolder;
use craft\services\Volumes;
use craft\test\Fixture;

/**
 * Class VolumeFolderFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class VolumesFolderFixture extends Fixture
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        parent::load();

        Craft::$app->set('volumes', new Volumes());
    }
}
