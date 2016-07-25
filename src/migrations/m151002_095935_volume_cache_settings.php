<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\Json;

/**
 * m151002_095935_volume_cache_settings migration.
 */
class m151002_095935_volume_cache_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Update how cache settings are stored for S3 and Google Cloud Volumes.
        $volumes = (new Query())
            ->select('id, settings')
            ->from('{{%volumes}}')
            ->where(['like', 'type', '%AwsS3', false])
            ->orWhere(['like', 'type', '%GoogleCloud', false])
            ->all();

        foreach ($volumes as $volume) {
            $settings = Json::decode($volume['settings']);

            if (!empty($settings['expires']) && preg_match('/([0-9]+)([a-z]+)/', $settings['expires'], $matches)) {
                $settings['expires'] = $matches[1].' '.$matches[2];

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
        echo "m151002_095935_volume_cache_settings cannot be reverted.\n";

        return false;
    }
}
