<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220209_095604_add_indexes migration.
 */
class m220209_095604_add_indexes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        foreach ($this->_indexes() as $index) {
            $this->createIndexIfMissing(...$index);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        foreach ($this->_indexes() as $index) {
            $this->dropIndexIfExists(...$index);
        }

        return true;
    }

    private function _indexes(): array
    {
        return [
            [Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId', 'enabled'], false],
        ];
    }
}
