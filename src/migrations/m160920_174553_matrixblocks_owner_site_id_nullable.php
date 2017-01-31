<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m160920_174553_matrixblocks_owner_site_id_nullable migration.
 */
class m160920_174553_matrixblocks_owner_site_id_nullable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // The original v3 installer incorrectly set matrixblocks.ownerSiteId to NOT NULL
        if (!$this->db->getSchema()->getTableSchema('{{%matrixblocks}}')->getColumn('ownerSiteId')->allowNull) {
            $this->alterColumn('{{%matrixblocks}}', 'ownerSiteId', $this->integer());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160920_174553_matrixblocks_owner_site_id_nullable cannot be reverted.\n";

        return false;
    }
}
