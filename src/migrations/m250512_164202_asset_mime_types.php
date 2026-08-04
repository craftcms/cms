<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250512_164202_asset_mime_types migration.
 */
class m250512_164202_asset_mime_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ASSETS, 'mimeType', $this->string()->after('filename'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::ASSETS, 'mimeType')) {
            $this->dropColumn(Table::ASSETS, 'mimeType');
        }
        return true;
    }
}
