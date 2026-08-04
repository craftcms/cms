<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\services\ProjectConfig;

/**
 * m230616_173810_kill_field_groups migration.
 */
class m230616_173810_kill_field_groups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropAllForeignKeysToTable('{{%fieldgroups}}');
        $this->dropColumn(Table::FIELDS, 'groupId');
        $this->dropTable('{{%fieldgroups}}');

        $projectConfig = Craft::$app->getProjectConfig();
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        $projectConfig->remove('fieldGroups');
        foreach ($projectConfig->get(ProjectConfig::PATH_FIELDS) ?? [] as $fieldUid => $fieldConfig) {
            unset($fieldConfig['fieldGroup']);
            $projectConfig->set(sprintf('%s.%s', ProjectConfig::PATH_FIELDS, $fieldUid), $fieldConfig);
        }

        $projectConfig->muteEvents = $muteEvents;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230616_173810_kill_field_groups cannot be reverted.\n";
        return false;
    }
}
