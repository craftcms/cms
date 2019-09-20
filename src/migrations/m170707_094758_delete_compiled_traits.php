<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;

/**
 * m170707_094758_delete_compiled_traits migration.
 */
class m170707_094758_delete_compiled_traits extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $files = ['ContentTrait', 'ElementQueryTrait'];
        $compiledClassesPath = Craft::$app->getPath()->getCompiledClassesPath();

        foreach ($files as $file) {
            $path = $compiledClassesPath . DIRECTORY_SEPARATOR . $file . '.php';
            if (file_exists($path)) {
                echo "    > removing $path ...";
                FileHelper::unlink($path);
                echo " done\n";
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170707_094758_delete_compiled_traits cannot be reverted.\n";
        return false;
    }
}
