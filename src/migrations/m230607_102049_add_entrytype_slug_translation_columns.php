<?php

namespace craft\migrations;

use craft\base\Field;
use craft\db\Migration;
use craft\db\Table;

/**
 * m230607_102049_add_entrytype_slug_translation_columns migration.
 */
class m230607_102049_add_entrytype_slug_translation_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::ENTRYTYPES);

        if (!isset($table->columns['slugTranslationMethod'])) {
            $this->addColumn(
                Table::ENTRYTYPES,
                'slugTranslationMethod',
                $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE)->after('titleFormat'),
            );
        }
        if (!isset($table->columns['slugTranslationKeyFormat'])) {
            $this->addColumn(
                Table::ENTRYTYPES,
                'slugTranslationKeyFormat',
                $this->text()->after('slugTranslationMethod'),
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::ENTRYTYPES);
        if (isset($table->columns['slugTranslationMethod'])) {
            $this->dropColumn(Table::ENTRYTYPES, 'slugTranslationMethod');
        }
        if (isset($table->columns['slugTranslationKeyFormat'])) {
            $this->dropColumn(Table::ENTRYTYPES, 'slugTranslationKeyFormat');
        }
        return true;
    }
}
