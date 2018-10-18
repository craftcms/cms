<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
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

        // Don't overwrite existing system config if there already is one
        if ($projectConfig->get('system') === null) {
            $info = (new Query())
                ->select(['edition', 'name', 'timezone', 'on'])
                ->from('{{%info}}')
                ->one();

            $projectConfig->set('system', [
                'edition' => App::editionHandle((int)$info['edition']),
                'live' => (bool)$info['on'],
                'name' => $info['name'],
                'schemaVersion' => $projectConfig->get('schemaVersion'),
                'timeZone' => $info['timezone'],
            ]);
        }

        // Drop the old top-level schemaVersion config setting
        $projectConfig->remove('schemaVersion');

        // Drop the columns from the info table
        $this->dropColumn('{{%info}}', 'edition');
        $this->dropColumn('{{%info}}', 'name');
        $this->dropColumn('{{%info}}', 'timezone');
        $this->dropColumn('{{%info}}', 'on');
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
