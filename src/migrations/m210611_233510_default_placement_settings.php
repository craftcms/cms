<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\models\CategoryGroup;
use craft\models\Section;

/**
 * m210611_233510_default_placement_settings migration.
 */
class m210611_233510_default_placement_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::CATEGORYGROUPS, 'defaultPlacement',
            $this->enum('defaultPlacement', [CategoryGroup::DEFAULT_PLACEMENT_BEGINNING, CategoryGroup::DEFAULT_PLACEMENT_END])
                ->defaultValue(CategoryGroup::DEFAULT_PLACEMENT_END)
                ->notNull()
                ->after('handle'));

        $this->addColumn(Table::SECTIONS, 'defaultPlacement',
            $this->enum('defaultPlacement', [Section::DEFAULT_PLACEMENT_BEGINNING, Section::DEFAULT_PLACEMENT_END])
                ->defaultValue(Section::DEFAULT_PLACEMENT_END)
                ->notNull()
                ->after('propagationMethod'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn(Table::CATEGORYGROUPS, 'defaultPlacement');
        $this->dropColumn(Table::SECTIONS, 'defaultPlacement');
    }
}
