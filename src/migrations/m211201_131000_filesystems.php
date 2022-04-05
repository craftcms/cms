<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig;
use yii\db\Expression;

/**
 * m211201_131000_filesystems migration.
 */
class m211201_131000_filesystems extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Add the fs column and set it to the volume handles as a starting point
        $this->addColumn(Table::VOLUMES, 'fs', $this->string()->after('type'));

        $this->update(Table::VOLUMES, [
            'fs' => new Expression('[[handle]]'),
        ], updateTimestamp: false);

        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute(sprintf('alter table %s alter column [[fs]] set not null', Table::VOLUMES));
        } else {
            $this->alterColumn(Table::VOLUMES, 'fs', $this->string()->notNull());
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $volumes = $projectConfig->get(ProjectConfig::PATH_VOLUMES) ?? [];
            $filesystems = [];

            foreach ($volumes as &$volumeData) {
                // Convert built-in volume types to FS types
                $type = StringHelper::replaceBeginning($volumeData['type'], 'craft\\volumes\\', 'craft\\fs\\');

                $filesystems[$volumeData['handle']] = [
                    'name' => $volumeData['name'],
                    'type' => $type,
                    'hasUrls' => $volumeData['hasUrls'],
                    'url' => $volumeData['url'],
                    'settings' => $volumeData['settings'],
                ];

                $volumeData['fs'] = $volumeData['handle'];
                unset($volumeData['settings'], $volumeData['type']);
            }
            unset($volumeData);

            $projectConfig->set(ProjectConfig::PATH_FS, $filesystems);
            $projectConfig->set(ProjectConfig::PATH_VOLUMES, $volumes);
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
        echo "m211201_131000_filesystems cannot be reverted.\n";
        return false;
    }
}
