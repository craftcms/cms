<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190624_234204_matrix_propagation_method migration.
 */
class m190624_234204_matrix_propagation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropForeignKeyIfExists(Table::MATRIXBLOCKS, ['ownerSiteId'], $this);
        MigrationHelper::dropIndexIfExists(Table::MATRIXBLOCKS, ['ownerSiteId'], false, $this);
        $this->dropColumn(Table::MATRIXBLOCKS, 'ownerSiteId');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190624_234204_matrix_propagation_method cannot be reverted.\n";
        return false;
    }
}
