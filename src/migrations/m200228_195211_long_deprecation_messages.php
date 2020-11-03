<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200228_195211_long_deprecation_messages migration.
 */
class m200228_195211_long_deprecation_messages extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(Table::DEPRECATIONERRORS, 'message', $this->text());
    }
}
