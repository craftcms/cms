<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use yii\helpers\Inflector;

/**
 * m150403_184247_plugins_table_changes migration.
 */
class m150403_184247_plugins_table_changes extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists(Table::PLUGINS, 'class')) {
            MigrationHelper::dropIndexIfExists(Table::PLUGINS, ['class'], true, $this);
            MigrationHelper::renameColumn(Table::PLUGINS, 'class', 'handle', $this);
        }

        // Kebab-case the plugin handles
        $plugins = (new Query())
            ->select(['id', 'handle'])
            ->from([Table::PLUGINS])
            ->all();

        foreach ($plugins as $plugin) {
            $newHandle = Inflector::camel2id($plugin['handle']);

            if ($newHandle !== $plugin['handle']) {
                $this->update(Table::PLUGINS, ['handle' => $newHandle], ['id' => $plugin['id']]);
            }
        }

        $this->createIndex(null, Table::PLUGINS, ['handle'], true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150403_184247_plugins_table_changes cannot be reverted.\n";

        return false;
    }
}
