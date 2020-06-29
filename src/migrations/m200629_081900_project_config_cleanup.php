<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\services\Sections;
use craft\services\UserGroups;

/**
 * m200629_081900_project_config_cleanup migration.
 */
class m200629_081900_project_config_cleanup extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.5.6', '<')) {
            $muted = $projectConfig->muteEvents;
            $projectConfig->muteEvents = true;

            // Add user group descriptions to project config
            $groupData = (new Query())
                ->select(['uid', 'description'])
                ->from([Table::USERGROUPS])
                ->pairs();

            foreach ($groupData as $uid => $description) {
                $projectConfig->set(UserGroups::CONFIG_USERPGROUPS_KEY . '.' . $uid . '.description', (string)$description);
            }

            // Add the new entry type config values to the project config.
            $entryTypeData = (new Query())
                ->select([
                    's.uid as sectionUid',
                    'et.uid as entryTypeUid',
                    'et.titleTranslationMethod',
                    'et.titleTranslationKeyFormat',
                    'et.titleInstructions'
                ])
                ->from(['et' => Table::ENTRYTYPES])
                ->innerJoin(['s' => Table::SECTIONS], '[[et.sectionId]] = [[s.id]]')
                ->all();

            foreach ($entryTypeData as $row) {
                $basePath = Sections::CONFIG_SECTIONS_KEY . '.' . $row['sectionUid'] . '.' . Sections::CONFIG_ENTRYTYPES_KEY . '.' . (string)$row['entryTypeUid'];
                $projectConfig->set($basePath . '.titleTranslationMethod', (string)$row['titleTranslationMethod']);
                $projectConfig->set($basePath . '.titleTranslationKeyFormat', (string)$row['titleTranslationKeyFormat']);
                $projectConfig->set($basePath . '.titleInstructions', (string)$row['titleInstructions']);
            }

            $projectConfig->muteEvents = $muted;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200629_081900_project_config_cleanup cannot be reverted.\n";
        return false;
    }
}
