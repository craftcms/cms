<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

/**
 * m160707_000001_rename_richtext_assetsource_setting migration.
 */
class m160707_000001_rename_richtext_assetsource_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FIELDS])
            ->where(['type' => 'craft\\redactor\\Field'])
            ->all($this->db);

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            if (is_array($settings) && array_key_exists('availableAssetSources', $settings)) {
                $settings['availableVolumes'] = ArrayHelper::remove($settings, 'availableAssetSources');
                $this->update(Table::FIELDS, ['settings' => Json::encode($settings)], ['id' => $field['id']]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160707_000001_rename_richtext_assetsource_setting cannot be reverted.\n";

        return false;
    }
}
