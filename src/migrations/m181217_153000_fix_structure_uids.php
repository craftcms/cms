<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\models\Section;
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
        // Get the section data from the project config
        $pcSections = Craft::$app->getProjectConfig()->get(Sections::CONFIG_SECTIONS_KEY) ?? [];

        // Ensure that the Structure sections' UIDs in the database match the project config
        foreach ($pcSections as $sectionUid => $sectionData) {
            if (empty($sectionData['type']) || $sectionData['type'] !== Section::TYPE_STRUCTURE) {
                continue;
            }

            $structureUid = $sectionData['structure']['uid'];
            $structureId = (new Query())
                ->select(['structureId'])
                ->from(Table::SECTIONS)
                ->where(['uid' => $sectionUid])
                ->scalar();

            if (!$structureId) {
                continue;
            }

            $this->update(Table::STRUCTURES, [
                'uid' => $structureUid,
            ], ['id' => $structureId], [], false);
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
