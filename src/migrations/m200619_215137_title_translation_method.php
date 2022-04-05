<?php

namespace craft\migrations;

use craft\base\Field;
use craft\db\Migration;
use craft\db\Table;

/**
 * m200619_215137_title_translation_method migration.
 */
class m200619_215137_title_translation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ENTRYTYPES, 'titleTranslationMethod', $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE)->after('hasTitleField'));
        $this->addColumn(Table::ENTRYTYPES, 'titleTranslationKeyFormat', $this->text()->after('titleTranslationMethod'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200619_215137_title_translation_method cannot be reverted.\n";
        return false;
    }
}
