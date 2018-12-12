<?php

namespace craft\migrations;

use craft\db\Migration;

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

        $this->alterColumn('{{%assetindexdata}}', 'volumeId', $this->integer()->notNull());
        $this->alterColumn('{{%assetindexdata}}', 'offset', $this->integer()->notNull());
        $this->alterColumn('{{%assetindexdata}}', 'recordId', $this->integer());
        $this->alterColumn('{{%assettransforms}}', 'height', $this->integer()->unsigned());
        $this->alterColumn('{{%assettransforms}}', 'width', $this->integer()->unsigned());
        $this->alterColumn('{{%deprecationerrors}}', 'template', $this->string(500));
        $this->alterColumn('{{%emailmessages}}', 'key', $this->string()->notNull());
        $this->alterColumn('{{%emailmessages}}', 'subject', $this->text()->notNull());
        $this->alterColumn('{{%globalsets}}', 'fieldLayoutId', $this->integer());
        $this->alterColumn('{{%routes}}', 'template', $this->string(500)->notNull());
        $this->alterColumn('{{%structureelements}}', 'root', $this->integer()->unsigned());
        $this->alterColumn('{{%structureelements}}', 'lft', $this->integer()->notNull()->unsigned());
        $this->alterColumn('{{%structureelements}}', 'rgt', $this->integer()->notNull()->unsigned());
        $this->alterColumn('{{%taggroups}}', 'fieldLayoutId', $this->integer());

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
