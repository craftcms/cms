<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220213_015220_matrixblocks_elements_table migration.
 */
class m220213_015220_matrixblocks_owners_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::MATRIXBLOCKS_OWNERS);

        $this->createTable(Table::MATRIXBLOCKS_OWNERS, [
            'blockId' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'PRIMARY KEY([[blockId]], [[ownerId]])',
        ]);

        $this->addForeignKey(null, Table::MATRIXBLOCKS_OWNERS, ['blockId'], Table::MATRIXBLOCKS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKS_OWNERS, ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);

        $blocksTable = Table::MATRIXBLOCKS;
        $elementsTable = Table::ELEMENTS;
        $ownersTable = Table::MATRIXBLOCKS_OWNERS;

        // Delete orphaned block rows
        if ($this->db->getIsMysql()) {
            $this->execute(<<<SQL
DELETE [[b]].* FROM $blocksTable [[b]]
LEFT JOIN $elementsTable [[e]] ON [[e.id]] = [[b.ownerId]]
WHERE [[e.id]] IS NULL
SQL);
        } else {
            $this->execute(<<<SQL
DELETE FROM $blocksTable
USING $blocksTable [[b]]
LEFT JOIN $elementsTable [[e]] ON [[e.id]] = [[b.ownerId]]
WHERE
  $blocksTable.[[id]] = [[b.id]] AND
  [[e.id]] IS NULL
SQL);
        }

        $this->execute(<<<SQL
INSERT INTO $ownersTable ([[blockId]], [[ownerId]], [[sortOrder]]) 
SELECT [[id]], [[ownerId]], COALESCE([[sortOrder]], 1) 
FROM $blocksTable
SQL);

        // drop sortOrder
        $this->dropIndexIfExists(Table::MATRIXBLOCKS, ['sortOrder'], false);
        $this->dropColumn(Table::MATRIXBLOCKS, 'sortOrder');

        // ownerId => primaryOwnerId
        $this->renameColumn(Table::MATRIXBLOCKS, 'ownerId', 'primaryOwnerId');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220213_015220_matrixblocks_owners_table cannot be reverted.\n";
        return false;
    }
}
