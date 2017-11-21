<?php

namespace craft\migrations;

use craft\db\Migration;
use yii\db\Expression;

/**
 * m170630_161028_deprecation_changes migration.
 */
class m170630_161028_deprecation_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = '{{%deprecationerrors}}';

        // Copy this from m170821_180624_deprecation_line_nullable in case any template lines are null
        $this->alterColumn($table, 'line', $this->smallInteger()->unsigned());

        $this->update($table, [
            'file' => new Expression('[[template]]'),
            'line' => new Expression('[[templateLine]]')
        ], ['not', ['template' => null]]);

        $this->dropColumn($table, 'class');
        $this->dropColumn($table, 'method');
        $this->dropColumn($table, 'template');
        $this->dropColumn($table, 'templateLine');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170630_161028_deprecation_changes cannot be reverted.\n";
        return false;
    }
}
