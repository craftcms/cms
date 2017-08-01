<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
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
        if ($this->db->columnExists('{{%plugins}}', 'class')) {
            MigrationHelper::dropIndexIfExists('{{%plugins}}', ['class'], true, $this);
            MigrationHelper::renameColumn('{{%plugins}}', 'class', 'handle', $this);
        }

        // Kebab-case the plugin handles
        $plugins = (new Query())
            ->select(['id', 'handle'])
            ->from(['{{%plugins}}'])
            ->all();

        foreach ($plugins as $plugin) {
            $newHandle = Inflector::camel2id($plugin['handle']);

            if ($newHandle !== $plugin['handle']) {
                $this->update('{{%plugins}}', ['handle' => $newHandle], ['id' => $plugin['id']]);
            }
        }

        $this->createIndex(null, '{{%plugins}}', ['handle'], true);
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
