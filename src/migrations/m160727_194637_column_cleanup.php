<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m160727_194637_column_cleanup migration.
 */
class m160727_194637_column_cleanup extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Disable FK checks
        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false));

        $this->alterColumn(Table::ASSETINDEXDATA, 'volumeId', $this->integer()->notNull());
        $this->alterColumn(Table::ASSETINDEXDATA, 'offset', $this->integer()->notNull());
        $this->alterColumn(Table::ASSETINDEXDATA, 'recordId', $this->integer());
        $this->alterColumn(Table::ASSETTRANSFORMS, 'height', $this->integer()->unsigned());
        $this->alterColumn(Table::ASSETTRANSFORMS, 'width', $this->integer()->unsigned());
        $this->alterColumn(Table::DEPRECATIONERRORS, 'template', $this->string(500));
        $this->alterColumn('{{%emailmessages}}', 'key', $this->string()->notNull());
        $this->alterColumn('{{%emailmessages}}', 'subject', $this->text()->notNull());
        $this->alterColumn(Table::GLOBALSETS, 'fieldLayoutId', $this->integer());
        $this->alterColumn('{{%routes}}', 'template', $this->string(500)->notNull());
        $this->alterColumn(Table::STRUCTUREELEMENTS, 'root', $this->integer()->unsigned());
        $this->alterColumn(Table::STRUCTUREELEMENTS, 'lft', $this->integer()->notNull()->unsigned());
        $this->alterColumn(Table::STRUCTUREELEMENTS, 'rgt', $this->integer()->notNull()->unsigned());
        $this->alterColumn(Table::TAGGROUPS, 'fieldLayoutId', $this->integer());

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160727_194637_column_cleanup cannot be reverted.\n";

        return false;
    }
}
