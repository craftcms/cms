<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m260120_120907_line_breaks_in_titles migration.
 */
class m260120_120907_line_breaks_in_titles extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::ENTRYTYPES, 'allowLineBreaksInTitles')) {
            $this->addColumn(Table::ENTRYTYPES, 'allowLineBreaksInTitles', $this->boolean()->notNull()->defaultValue(false));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::ENTRYTYPES, 'allowLineBreaksInTitles')) {
            $this->dropColumn(Table::ENTRYTYPES, 'allowLineBreaksInTitles');
        }

        return true;
    }
}
