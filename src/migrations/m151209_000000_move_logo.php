<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use yii\base\ErrorException;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151209_000000_move_logo extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $pathService = Craft::$app->getPath();
        try {
            rename($pathService->getStoragePath().DIRECTORY_SEPARATOR.'logo', $pathService->getRebrandPath().DIRECTORY_SEPARATOR.'logo');
        } catch (ErrorException $e) {
            Craft::warning('Unable to rename the logo path: '.$e->getMessage(), __METHOD__);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151209_000000_move_logo cannot be reverted.\n";

        return false;
    }
}
