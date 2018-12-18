<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craftunit\fixtures;

use craft\records\Volume;
use craft\test\Fixture;
use yii\test\ActiveFixture;

/**
 * Class VolumesFixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class VolumesFixture extends Fixture
{
    public $modelClass = Volume::class;
    public $dataFile = __DIR__.'/data/volumes.php';

    const BASE_URL = 'https://cdn.test.craftcms.dev/';
}