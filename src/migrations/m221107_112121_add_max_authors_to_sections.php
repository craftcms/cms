<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m221107_112121_add_max_authors_to_sections migration.
 */
class m221107_112121_add_max_authors_to_sections extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // add maxAuthors to section
        $this->addColumn(
            Table::SECTIONS,
            'maxAuthors',
            $this->smallInteger()->unsigned()->defaultValue(1)->notNull()->after('enableVersioning')
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::SECTIONS, 'maxAuthors');
        return true;
    }
}
