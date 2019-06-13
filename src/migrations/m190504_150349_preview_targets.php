<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m190504_150349_preview_targets migration.
 */
class m190504_150349_preview_targets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::SECTIONS, 'previewTargets', $this->text()->after('propagationMethod'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190504_150349_preview_targets cannot be reverted.\n";
        return false;
    }
}
