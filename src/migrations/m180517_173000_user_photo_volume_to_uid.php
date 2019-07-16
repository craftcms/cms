<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

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

            if (array_key_exists('photoVolumeId', $settings)) {
                if (empty($settings['photoVolumeId'])) {
                    $settings['photoVolumeUid'] = null;
                } else {
                    $volumeUid = (new Query())
                        ->select(['uid'])
                        ->where(['id' => $settings['photoVolumeId']])
                        ->from([Table::VOLUMES])
                        ->scalar();

                    if ($volumeUid) {
                        $settings['photoVolumeUid'] = $volumeUid;
                    } else {
                        $settings['photoVolumeUid'] = null;
                    }
                }

                unset($settings['photoVolumeId']);

                $this->update('{{%systemsettings}}', [
                    'settings' => Json::encode($settings),
                ], [
                    'category' => 'users'
                ], [], false);
            }
        }
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
