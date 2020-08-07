<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200715_113400_transform_index_error_flag migration.
 */
class m200715_113400_transform_index_error_flag extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ASSETTRANSFORMINDEX, 'error', $this->boolean()->defaultValue(false)->notNull()->after('inProgress'));
    }
}
