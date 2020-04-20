<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;

/**
 * m151002_095935_volume_cache_settings migration.
 */
class m151002_095935_volume_cache_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Update how cache settings are stored for S3 and Google Cloud Volumes.
        $volumes = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::VOLUMES])
            ->where([
                'or',
                ['like', 'type', '%AwsS3', false],
                ['like', 'type', '%GoogleCloud', false]
            ])
            ->all($this->db);

        foreach ($volumes as $volume) {
            $settings = Json::decode($volume['settings']);

            if (!empty($settings['expires']) && preg_match('/(\d+)([a-z]+)/', $settings['expires'], $matches)) {
                $settings['expires'] = $matches[1] . ' ' . $matches[2];

                Db::update(Table::VOLUMES, [
                    'settings' => Json::encode($settings)
                ], [
                    'id' => $volume['id'],
                ], [], true, $this->db);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151002_095935_volume_cache_settings cannot be reverted.\n";

        return false;
    }
}
