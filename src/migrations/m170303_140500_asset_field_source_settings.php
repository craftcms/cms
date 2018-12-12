<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\fields\Assets;
use craft\helpers\Json;

/**
 * m170303_140500_asset_field_source_settings migration.
 */
class m170303_140500_asset_field_source_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        echo "    > Converting the field setting format \n";
        // Convert the field setting from volume id to folder:XX
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['type' => Assets::class])
            ->all($this->db);

        $getFolderPathFromVolumeId = function($volumeId) {
            if (empty($volumeId)) {
                return '';
            }

            $folderId = (new Query())
                ->select(['id'])
                ->from(['{{%volumefolders}}'])
                ->where(['parentId' => null])
                ->andWhere(['volumeId' => $volumeId])
                ->scalar($this->db);

            // If the folder does not exist, set an invalid id to trigger field error when viewing
            return $folderId ? 'folder:' . $folderId : 'folder:0';
        };

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            if (!empty($settings['defaultUploadLocationSource'])) {
                $settings['defaultUploadLocationSource'] = $getFolderPathFromVolumeId($settings['defaultUploadLocationSource']);
            }

            if (!empty($settings['singleUploadLocationSource'])) {
                $settings['singleUploadLocationSource'] = $getFolderPathFromVolumeId($settings['singleUploadLocationSource']);
            }

            $settings = Json::encode($settings);

            $this->update('{{%fields}}', ['settings' => $settings], ['id' => $field['id']]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170303_140500_asset_field_source_settings cannot be reverted.\n";

        return false;
    }
}
