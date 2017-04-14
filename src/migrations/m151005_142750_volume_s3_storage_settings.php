<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * m151002_095935_volume_cache_settings migration.
 */
class m151005_142750_volume_s3_storage_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Update AWS S3 Volumes to have information about storage class.
        $volumes = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%volumes}}'])
            ->where(['like', 'type', '%AwsS3', false])
            ->all($this->db);

        foreach ($volumes as $volume) {
            $settings = Json::decode($volume['settings']);

            if (empty($settings['storageClass'])) {
                $settings['storageClass'] = 'STANDARD'; // value of \craft\base\Volume::STORAGE_STANDARD

                Craft::$app->getDb()->createCommand()
                    ->update(
                        '{{%volumes}}',
                        ['settings' => Json::encode($settings)],
                        ['id' => $volume['id']])
                    ->execute();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151005_142750_volume_s3_storage_settings cannot be reverted.\n";

        return false;
    }
}
