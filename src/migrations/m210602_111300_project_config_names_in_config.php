<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\services\ProjectConfig;

/**
 * m210602_111300_project_config_names_in_config migration.
 */
class m210602_111300_project_config_names_in_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tableExists = Craft::$app->getDb()->getSchema()->getTableSchema('{{%projectconfignames}}');

        if ($tableExists) {
            $names = (new Query())->select(['uid', 'name'])->from(['{{%projectconfignames}}'])->pairs();

            $projectConfig = Craft::$app->getProjectConfig();
            $schemaVersion = $projectConfig->get('system.schemaVersion', true);

            if (version_compare($schemaVersion, '3.7.4', '<')) {
                $projectConfig->set(ProjectConfig::CONFIG_NAMES_KEY, $names);
            }

            $this->dropTableIfExists('{{%projectconfignames}}');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210602_111300_project_config_names_in_config cannot be reverted.\n";
        return false;
    }
}
