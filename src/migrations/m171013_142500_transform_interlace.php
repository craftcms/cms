<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m171013_142500_transform_interlace migration.
 */
class m171013_142500_transform_interlace extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ASSETTRANSFORMS, 'interlace', $this->enum('interlace', ['none', 'line', 'plane', 'partition'])->after('quality')->notNull()->defaultValue('none'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171013_142500_transform_interlace cannot be reverted.\n";
        return false;
    }
}
