<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craftunit\fixtures;

use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\records\Volume;
use craft\services\Volumes;
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

    public function load()
    {
        parent::load();

        \Craft::$app->set('volumes', new Volumes());
    }

    public function unload()
    {
        // Clear the dir
        foreach ($this->getData() as $data) {
            $settings = Json::decodeIfJson($data['settings']);
            FileHelper::clearDirectory($settings['path']);
        }

        parent::unload();
    }
}