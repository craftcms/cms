<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250531_183058_content_blocks migration.
 */
class m250531_183058_content_blocks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->safeDown();

        $this->createTable(Table::CONTENTBLOCKS, [
            'id' => $this->integer()->notNull(),
            'primaryOwnerId' => $this->integer(),
            'fieldId' => $this->integer(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, Table::CONTENTBLOCKS, ['primaryOwnerId'], false);
        $this->createIndex(null, Table::CONTENTBLOCKS, ['fieldId'], false);

        $this->addForeignKey(null, Table::CONTENTBLOCKS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CONTENTBLOCKS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CONTENTBLOCKS, ['primaryOwnerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::CONTENTBLOCKS);
        return true;
    }
}
