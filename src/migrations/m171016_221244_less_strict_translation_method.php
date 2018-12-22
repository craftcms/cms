<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m171016_221244_less_strict_translation_method migration.
 */
class m171016_221244_less_strict_translation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute("ALTER TABLE {{%fields}} DROP CONSTRAINT [[{$this->db->tablePrefix}fields_translationMethod_check]]");
        } else {
            $this->alterColumn('{{%fields}}', 'translationMethod', $this->string()->notNull()->defaultValue('none'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171016_221244_less_strict_translation_method cannot be reverted.\n";
        return false;
    }
}
