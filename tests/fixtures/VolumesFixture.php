<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\records\Volume;
use craft\services\Volumes;
use craft\test\Fixture;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * Class VolumesFixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class VolumesFixture extends Fixture
{
    // Constants
    // =========================================================================

    const BASE_URL = 'https://cdn.test.craftcms.test/';

    // Properties
    // =========================================================================

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
    public $depends = [FieldLayoutFixture::class];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function load()
    {
        parent::load();

        // Create the dirs
        foreach ($this->getData() as $data) {
            $settings = Json::decodeIfJson($data['settings']);
            FileHelper::createDirectory($settings['path']);
        }

        Craft::$app->set('volumes', new Volumes());
    }

    /**
     * @inheritdoc
     * @throws ErrorException
     */
    public function unload()
    {
        // Remove the dirs
        foreach ($this->getData() as $data) {
            $settings = Json::decodeIfJson($data['settings']);
            FileHelper::removeDirectory($settings['path']);
        }

        parent::unload();
    }
}
