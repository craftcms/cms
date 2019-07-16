<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
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
        $this->update(Table::DEPRECATIONERRORS, [
            'file' => new Expression('[[template]]'),
            'line' => new Expression('[[templateLine]]')
        ], ['not', ['template' => null]]);

        $this->dropColumn(Table::DEPRECATIONERRORS, 'class');
        $this->dropColumn(Table::DEPRECATIONERRORS, 'method');
        $this->dropColumn(Table::DEPRECATIONERRORS, 'template');
        $this->dropColumn(Table::DEPRECATIONERRORS, 'templateLine');
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
