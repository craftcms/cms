<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m180124_230459_fix_propagate_entries_values migration.
 */
class m180124_230459_fix_propagate_entries_values extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Any sections with incorrect propagateEntries values?
        $sectionIds = (new Query())
            ->select(['id'])
            ->from([Table::SECTIONS])
            ->where(['type' => ['single', 'structure'], 'propagateEntries' => false])
            ->column();

        if (!empty($sectionIds)) {
            $sectionsService = Craft::$app->getSections();

            foreach ($sectionIds as $sectionId) {
                $section = $sectionsService->getSectionById($sectionId);
                $sectionsService->saveSection($section, false);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180124_230459_fix_propagate_entries_values cannot be reverted.\n";
        return false;
    }
}
