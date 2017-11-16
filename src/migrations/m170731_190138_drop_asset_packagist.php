<?php

namespace craft\migrations;

use Composer\Json\JsonFile;
use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;
use yii\base\Exception;

/**
 * m170731_190138_drop_asset_packagist migration.
 */
class m170731_190138_drop_asset_packagist extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // See if we can find composer.json
        try {
            $jsonPath = Craft::$app->getComposer()->getJsonPath();
        } catch (Exception $e) {
            Craft::warning('Could not remove the asset-packagist.org repo from composer.json because composer.json could not be found.', __METHOD__);
            return true;
        }

        // See if we can write to composer.json
        if (!FileHelper::isWritable($jsonPath)) {
            Craft::warning('Could not remove the asset-packagist.org repo from composer.json because we cannot write to composer.json.', __METHOD__);
            return true;
        }

        // Get the Composer config
        $json = new JsonFile($jsonPath);
        $config = $json->read();

        if (!isset($config['repositories'])) {
            return true;
        }

        // Remove the asset-packagist repo, if it's in there
        foreach ($config['repositories'] as $key => $repo) {
            if (isset($repo['url']) && strpos($repo['url'], '//asset-packagist.org') !== false) {
                unset($config['repositories'][$key]);
                // Reset the keys if numeric
                if (is_numeric($key)) {
                    $config['repositories'] = array_merge($config['repositories']);
                }
                $json->write($config);
                break;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170731_190138_drop_asset_packagist cannot be reverted.\n";
        return false;
    }
}
