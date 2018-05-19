<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

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
        $this->addColumn('{{%users}}', 'hasDashboard', $this->boolean()->notNull()->defaultValue(false)->after('lockoutDate'));

        $usersWithWidgets = (new Query())
            ->select(['userId'])
            ->distinct()
            ->from(['{{%widgets}}'])
            ->column();

        $this->update('{{%users}}', [
            'hasDashboard' => true,
        ], [
            'id' => $usersWithWidgets,
        ], [], false);

        $this->delete('{{%widgets}}', ['enabled' => false]);
        $this->dropColumn('{{%widgets}}', 'enabled');
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
