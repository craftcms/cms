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
use craft\records\Filesystem;
use craft\services\Filesystems;
use craft\test\ActiveFixture;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * Class FilesystemsFixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 4.0.0
 */
class FilesystemsFixture extends ActiveFixture
{
    const BASE_URL = 'https://cdn.test.craftcms.test/';

    /**
     * @inheritdoc
     */
    public $modelClass = Filesystem::class;

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/filesystems.php';

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function load(): void
    {
        parent::load();

        // Create the dirs
        foreach ($this->getData() as $data) {
            $settings = Json::decodeIfJson($data['settings']);
            FileHelper::createDirectory($settings['path']);
        }

        Craft::$app->set('filesystems', new Filesystems());
    }

    /**
     * @inheritdoc
     * @throws ErrorException
     */
    public function unload(): void
    {
        // Remove the dirs
        foreach ($this->getData() as $data) {
            $settings = Json::decodeIfJson($data['settings']);
            FileHelper::removeDirectory($settings['path']);
        }

        parent::unload();
    }
}
