<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;

/**
 * m170913_121007_move_security_key migration.
 */
class m170913_121007_move_security_key extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $pathService = Craft::$app->getPath();
        $oldPath = $pathService->getRuntimePath().DIRECTORY_SEPARATOR.'validation.key';
        $newPath = $pathService->getStoragePath().DIRECTORY_SEPARATOR.'security.key';
        if (file_exists($oldPath) && !file_exists($newPath)) {
            rename($oldPath, $newPath);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170913_121007_move_security_key cannot be reverted.\n";
        return false;
    }
}
