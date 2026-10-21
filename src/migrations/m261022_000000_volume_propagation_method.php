<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\enums\PropagationMethod;

/**
 * m261022_000000_volume_propagation_method migration.
 */
class m261022_000000_volume_propagation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::VOLUMES, 'propagationMethod')) {
            $this->addColumn(Table::VOLUMES, 'propagationMethod', $this->string()->defaultValue(PropagationMethod::All->value)->notNull()->after('altTranslationKeyFormat'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::VOLUMES, 'propagationMethod');

        return true;
    }
}
