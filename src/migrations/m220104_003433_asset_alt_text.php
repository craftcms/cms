<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220104_003433_asset_alt_text migration.
 */
class m220104_003433_asset_alt_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ASSETS, 'alt', $this->text()->after('kind'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::ASSETS, 'alt');
        return true;
    }
}
