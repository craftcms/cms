<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m190723_140314_fix_preview_targets_column migration.
 */
class m190723_140314_fix_preview_targets_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(Table::SECTIONS, 'previewTargets', $this->text());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190723_140314_fix_preview_targets_column cannot be reverted.\n";
        return false;
    }
}
