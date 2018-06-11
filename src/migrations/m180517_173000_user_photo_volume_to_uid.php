<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * m180517_173000_user_photo_volume_to_uid migration.
 */
class m180517_173000_user_photo_volume_to_uid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $settings = (new Query())
            ->select(['settings'])
            ->where(['category' => 'users'])
            ->from(['{{%systemsettings}}'])
            ->scalar();

        if ($settings) {
            $settings = Json::decodeIfJson($settings);

            if ($settings['photoVolumeId']) {
                $volumeUid = (new Query())
                    ->select(['uid'])
                    ->where(['id' => $settings['photoVolumeId']])
                    ->from('{{%volumes}}')
                    ->scalar();

                if ($volumeUid) {
                    $settings['photoVolumeUid'] = $volumeUid;
                    unset($settings['photoVolumeId']);
                    $this->update('{{%systemsettings}}', ['settings' => Json::encode($settings)], ['category' => 'users']);
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180517_173000_user_photo_volume_to_uid cannot be reverted.\n";

        return false;
    }
}
