<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\models\Section;
use craft\services\ProjectConfig;

/**
 * m181211_143040_fix_section_uids migration.
 */
class m181211_143040_fix_section_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get all entry types from project.yaml and all stored in DB.
        $projectConfig = Craft::$app->getProjectConfig();
        $sectionsService = Craft::$app->getSections();
        $configSections = $projectConfig->get($sectionsService::CONFIG_SECTIONS_KEY);

        $dbSections = (new Query())
            ->select([
                'id',
                'structureId',
                'name',
                'handle',
                'type',
                'enableVersioning',
                'propagateEntries',
                'uid',
            ])
            ->from(['{{%sections}}'])
            ->indexBy('uid')
            ->all();

        // For each section
        foreach ($configSections as $sectionUid => $sectionData) {
            $section = new Section($dbSections[$sectionUid]);

            $dbEntryTypes = $section->getEntryTypes();
            $configEntryTpes = $sectionData['entryTypes'];

            $byUid = [];
            $byHandle = [];

            // Key by UIDs and handles
            foreach ($dbEntryTypes as $dbEntryType) {
                $byUid[$dbEntryType->uid] = $dbEntryType;
                $byHandle[$dbEntryType->handle] = $dbEntryType;
            }

            $cleanupCandidates = [];

            // Remove all the valid data from arrays.
            foreach ($configEntryTpes as $uid => $entryType) {
                if (!empty($byUid[$uid])) {
                    unset($byHandle[$entryType['handle']]);
                    unset($byUid[$uid]);
                } else {
                    $cleanupCandidates[$uid] = $entryType;
                }
            }

            // Loop through the remaining data
            foreach ($cleanupCandidates as $uid => $entryType) {
                // If we're unable to find it by UID but are able to find it by handle, then change the UID in db to match the one in project config...
                if (empty($byUid[$uid]) && !empty($byHandle[$entryType['handle']])) {
                    $this->db->createCommand()->update('{{%entrytypes}}', ['uid' => $uid], ['id' => $byHandle[$entryType['handle']]->id])->execute();
                } else {
                    // otherwise this must be removed as there's no matching data.
                    $projectConfig->remove($sectionsService::CONFIG_SECTIONS_KEY . '.' . $sectionUid . '.entryTypes.' . $uid);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181211_143040_fix_section_uids cannot be reverted.\n";
        return false;
    }
}
