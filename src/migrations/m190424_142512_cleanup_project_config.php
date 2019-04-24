<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190424_142512_cleanup_project_config migration.
 */
class m190424_142512_cleanup_project_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.28', '>=')) {
            return;
        }

        $projectConfig->muteEvents = true;

        // Type-cast plugin enabled state
        $plugins = $projectConfig->get('plugins');
        if (!empty($plugins) && is_array($plugins)) {
            foreach ($plugins as $handle => $pluginData) {
                $projectConfig->set('plugins.' . $handle . '.enabled', (bool)$projectConfig->get('plugins.' . $handle . '.enabled'));
            }
        }

        // While we're at it, just make sure this is boolean, too.
        $projectConfig->set('system.live', (bool)$projectConfig->get('system.live'));

        $projectConfig->muteEvents = false;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190424_142512_cleanup_project_config cannot be reverted.\n";
        return false;
    }
}
