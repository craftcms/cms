<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use yii\helpers\Inflector;

/**
 * m170621_195237_format_plugin_handles migration.
 */
class m170621_195237_format_plugin_handles extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $path = Craft::$app->getVendorPath() . DIRECTORY_SEPARATOR . 'craftcms' . DIRECTORY_SEPARATOR . 'plugins.php';

        if (file_exists($path)) {
            /** @var array $plugins */
            $plugins = require $path;

            foreach ($plugins as $plugin) {
                // Is the plugin using the new handle kebab-case format?
                if (strpos($plugin['handle'], '-') !== false) {
                    $oldHandle = strtolower(str_replace('-', '', $plugin['handle']));
                    $newHandle = strtolower($plugin['handle']);
                } else {
                    $oldHandle = strtolower($plugin['handle']);
                    $newHandle = Inflector::camel2id($plugin['handle']);
                }

                if ($newHandle !== $oldHandle) {
                    $this->update('{{%plugins}}', ['handle' => $newHandle], ['handle' => $oldHandle]);

                    // Update user permissions
                    $oldName = 'accessplugin-' . strtolower($oldHandle);
                    $newName = 'accessplugin-' . $newHandle;
                    $this->update('{{%userpermissions}}', ['name' => $newName], ['name' => $oldName]);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170621_195237_format_plugin_handles cannot be reverted.\n";
        return false;
    }
}
