<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200625_131100_move_entrytypes_to_top_project_config migration.
 */
class m200625_131100_move_entrytypes_to_top_project_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.5.7', '<')) {
            $muted = $projectConfig->muteEvents;

            $projectConfig->muteEvents = true;
            $sections = $projectConfig->get('sections');

            if (!empty($sections)) {
                $entryTypes = [];

                // For each section, remove the entry type for separate storage.
                foreach ($sections as $uid => &$section) {
                    if (empty($section['entryTypes'])) {
                        continue;
                    }

                    foreach ($section['entryTypes'] as &$entryType) {
                        $entryType['section'] = $uid;
                        ksort($entryType);
                    }

                    $entryTypes += $section['entryTypes'];
                    unset($section['entryTypes']);
                }

                ksort($entryTypes);

                $projectConfig->set('sections', $sections);
                $projectConfig->set('entryTypes', $entryTypes);
            }

            $projectConfig->muteEvents = $muted;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200625_131100_move_entrytypes_to_top_project_config cannot be reverted.\n";
        return false;
    }
}
