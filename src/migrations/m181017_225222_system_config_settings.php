<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\App;

/**
 * m181017_225222_system_config_settings migration.
 */
class m181017_225222_system_config_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.3', '<')) {
            $info = (new Query())
                ->select(['edition', 'name', 'timezone', 'on'])
                ->from(Table::INFO)
                ->one();

            $projectConfig->set('system', [
                'edition' => App::editionHandle((int)$info['edition']),
                'live' => (bool)$info['on'],
                'name' => $info['name'],
                'timeZone' => $info['timezone'],
            ]);

            // Drop the old top-level schemaVersion config setting
            $projectConfig->remove('schemaVersion');
        }

        // Drop the columns from the info table
        $this->dropColumn(Table::INFO, 'edition');
        $this->dropColumn(Table::INFO, 'name');
        $this->dropColumn(Table::INFO, 'timezone');
        $this->dropColumn(Table::INFO, 'on');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181017_225222_system_config_settings cannot be reverted.\n";
        return false;
    }
}
