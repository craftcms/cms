<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\services\Sections;

/**
 * m181211_143040_fix_entry_type_uids migration.
 */
class m181211_143040_fix_entry_type_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        $canMakeConfigChanges = version_compare($schemaVersion, '3.1.10', '<');

        // Get all entry types from the DB
        $dbEntryTypes = (new Query())
            ->select(['et.id', 'et.uid', 'et.handle', 's.uid as sectionUid'])
            ->from(['et' => Table::ENTRYTYPES])
            ->innerJoin(['s' => Table::SECTIONS], '[[s.id]] = [[et.sectionId]]')
            ->all();

        // Index by section UID, handle
        $dbEntryTypes = ArrayHelper::index($dbEntryTypes, 'handle', ['sectionUid']);

        // Get the section data from the project config
        $pcSections = $projectConfig->get(Sections::CONFIG_SECTIONS_KEY);

        if (empty($pcSections)) {
            return;
        }

        // For each section, make sure the DB and project config UIDs match up
        foreach ($pcSections as $sectionUid => $pcSection) {
            $pcEntryTypes = $pcSection['entryTypes'] ?? [];

            foreach ($pcEntryTypes as $entryTypeUid => $pcEntryType) {
                // Do we have a matching entry type handle?
                $handle = $pcEntryType['handle'];
                if (isset($dbEntryTypes[$sectionUid][$handle])) {
                    // If the UIDs don't match, update the one in the DB
                    $dbEntryType = $dbEntryTypes[$sectionUid][$handle];
                    if ($dbEntryType['uid'] !== $entryTypeUid) {
                        $this->update(Table::ENTRYTYPES, [
                            'uid' => $entryTypeUid
                        ], [
                            'id' => $dbEntryType['id']
                        ], [], false);
                    }
                } else if ($canMakeConfigChanges) {
                    // Remove this entry type from the project config as there's no matching DB data
                    $projectConfig->remove(Sections::CONFIG_SECTIONS_KEY . '.' . $sectionUid . '.entryTypes.' . $entryTypeUid);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181211_143040_fix_entry_type_uids cannot be reverted.\n";
        return false;
    }
}
