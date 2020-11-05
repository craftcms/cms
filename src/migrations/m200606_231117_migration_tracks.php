<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m200606_231117_migration_tracks migration.
 */
class m200606_231117_migration_tracks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::MIGRATIONS, 'track', $this->string()->after('id'));

        // Craft & content migrations
        $this->update(Table::MIGRATIONS, [
            'track' => 'craft',
        ], [
            'type' => 'app',
        ], [], false);
        $this->update(Table::MIGRATIONS, [
            'track' => 'content',
        ], [
            'type' => 'content',
        ], [], false);

        // Plugin migrations
        $plugins = (new Query())
            ->select(['id', 'handle'])
            ->from([Table::PLUGINS])
            ->all();
        foreach ($plugins as $plugin) {
            $this->update(Table::MIGRATIONS, [
                'track' => "plugin:{$plugin['handle']}",
            ], [
                'pluginId' => $plugin['id'],
            ], [], false);
        }

        // Delete any rows that somehow still are missing a track (perhaps due to a missing FK on the old pluginId column)
        $this->delete(Table::MIGRATIONS, [
            'track' => null,
        ]);

        // Now we can set the track column to NOT NULL
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute('alter table {{%migrations}} alter column [[track]] set not null');
        } else {
            $this->alterColumn(Table::MIGRATIONS, 'track', $this->string()->notNull());
        }

        // Delete any duplicate rows
        $this->deleteDuplicates(Table::MIGRATIONS, ['track', 'name']);

        $this->createIndex(null, Table::MIGRATIONS, ['track', 'name'], true);
        MigrationHelper::dropForeignKeyIfExists(Table::MIGRATIONS, ['pluginId'], $this);
        MigrationHelper::dropIndexIfExists(Table::MIGRATIONS, ['pluginId'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::MIGRATIONS, ['type', 'pluginId'], false, $this);
        $this->dropColumn(Table::MIGRATIONS, 'type');
        $this->dropColumn(Table::MIGRATIONS, 'pluginId');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200606_231117_migration_tracks cannot be reverted.\n";
        return false;
    }
}
