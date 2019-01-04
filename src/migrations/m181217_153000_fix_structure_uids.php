<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\services\Sections;

/**
 * m181217_153000_fix_structure_uids migration.
 */
class m181217_153000_fix_structure_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $projectConfig = Craft::$app->getProjectConfig();

        // Get the section data from the project config
        $pcSections = $projectConfig->get(Sections::CONFIG_SECTIONS_KEY);

        if (empty($pcSections)) {
            return;
        }

        // For all sections...
        foreach ($pcSections as $sectionUid => $sectionData) {
            // ...that are strutures...
            if (!empty($sectionData['type']) && $sectionData['type'] === 'structure') {
                $structureUid = $sectionData['structure']['uid'];

                // ...fetch the matching DB section...
                $section = Craft::$app->getSections()->getSectionByUid($sectionUid);
                if ($section && $section->structureId) {

                    // ...and the matching structure...
                    $structure = Craft::$app->getStructures()->getStructureById($section->structureId);
                    if ($structure && $structure->uid !== $structureUid) {
                        // ...to make sure that the UIDs match.
                        $structure->uid = $structureUid;
                        Craft::$app->getStructures()->saveStructure($structure);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181217_153000_fix_structure_uids cannot be reverted.\n";
        return false;
    }
}
