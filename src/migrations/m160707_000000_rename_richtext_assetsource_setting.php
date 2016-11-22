<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160707_000000_rename_richtext_assetsource_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Update permissions
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['type' => 'craft\fields\RichText'])
            ->all();

        echo '';

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            if (!empty($settings['availableAssetSources'])) {
                $settings['availableVolumes'] = $settings['availableAssetSources'];
                unset($settings['availableAssetSources']);
                $this->update('{{%fields}}', ['settings' => Json::encode($settings)], ['id' => $field['id']]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160707_000000_rename_richtext_assetsource_setting cannot be reverted.\n";

        return false;
    }
}
