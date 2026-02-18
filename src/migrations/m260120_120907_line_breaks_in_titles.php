<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use yii\db\Exception as DbException;

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
            if ($this->db->getIsMysql()) {
                // Ensure the row format isn't COMPACT
                // (https://github.com/craftcms/cms/issues/18349)
                $schema = $this->db->getSchema();
                try {
                    $rowFormat = $schema->getRowFormat(Table::ENTRYTYPES);
                    if ($rowFormat === 'COMPACT') {
                        $schema->setRowFormat(Table::ENTRYTYPES, 'DYNAMIC');
                    }
                } catch (DbException) {
                }
            }

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
