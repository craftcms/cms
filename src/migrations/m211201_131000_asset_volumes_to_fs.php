<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig;

/**
 * m211201_131000_asset_volumes_to_fs migration.
 */
class m211201_131000_asset_volumes_to_fs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(Table::FILESYSTEMS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->string()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'url' => $this->string(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Table::FILESYSTEMS, ['handle']);

        $this->addColumn(Table::VOLUMES, 'filesystem', $this->string()->notNull()->after('type'));

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $volumes = $projectConfig->get(ProjectConfig::PATH_VOLUMES);

            $filesystems = [];
            foreach ($volumes as &$volumeData) {
                $fsHandle = $volumeData['handle'] . 'Filesystem';

                // Convert built-in volume types to FS types
                $type = StringHelper::replace($volumeData['type'], 'craft\\volumes', 'craft\\fs');

                // Do the same for the 3rd party volumes, that followed the same pattern
                $type = StringHelper::replace($type, '\\Volume', '\\Fs');

                $filesystems[StringHelper::UUID()] = [
                    'name' => $volumeData['name'] . ' Filesystem',
                    'handle' => $fsHandle,
                    'type' => $type,
                    'hasUrls' => $volumeData['hasUrls'],
                    'url' => $volumeData['url'],
                    'settings' => $volumeData['settings']
                ];

                $volumeData['filesystem'] = $fsHandle;

                unset($volumeData['settings'], $volumeData['type']);
            }
            unset($volumeData);

            foreach ($volumes as $uid => $volume) {
                $projectConfig->set(ProjectConfig::PATH_VOLUMES . '.' . $uid, $volume);
            }

            foreach ($filesystems as $uid => $fs) {
                $projectConfig->set(ProjectConfig::PATH_FILESYSTEMS . '.' . $uid, $fs);
            }
        }

        $this->dropColumn(Table::VOLUMES, 'settings');
        $this->dropColumn(Table::VOLUMES, 'type');
        $this->dropColumn(Table::VOLUMES, 'hasUrls');
        $this->dropColumn(Table::VOLUMES, 'url');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211201_131000_asset_volumes_to_fs cannot be reverted.\n";
        return false;
    }
}
