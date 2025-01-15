<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240926_202248_track_entries_deleted_with_section migration.
 */
class m240926_202248_track_entries_deleted_with_section extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRIES, 'deletedWithSection', $this->boolean()->null()->after('deletedWithEntryType'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::ENTRIES, 'deletedWithSection')) {
            $this->dropColumn(Table::ENTRIES, 'deletedWithSection');
        }
        return true;
    }
}
