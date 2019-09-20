<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m180418_205713_widget_cleanup migration.
 */
class m180418_205713_widget_cleanup extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::USERS, 'hasDashboard', $this->boolean()->notNull()->defaultValue(false)->after('lockoutDate'));

        $usersWithWidgets = (new Query())
            ->select(['userId'])
            ->distinct()
            ->from([Table::WIDGETS])
            ->column();

        $this->update(Table::USERS, [
            'hasDashboard' => true,
        ], [
            'id' => $usersWithWidgets,
        ], [], false);

        $this->delete(Table::WIDGETS, ['enabled' => false]);
        $this->dropColumn(Table::WIDGETS, 'enabled');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180418_205713_widget_cleanup cannot be reverted.\n";
        return false;
    }
}
