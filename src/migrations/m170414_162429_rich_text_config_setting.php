<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

/**
 * m170414_162429_rich_text_config_setting migration.
 */
class m170414_162429_rich_text_config_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get all of the Rich Text fields
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['type' => 'craft\\fields\\RichText'])
            ->all($this->db);

        // configFile => redactorConfig
        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $settings['redactorConfig'] = ArrayHelper::remove($settings, 'configFile');
            $this->update('{{%fields}}', [
                'settings' => Json::encode($settings)
            ], ['id' => $field['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170414_162429_rich_text_config_setting cannot be reverted.\n";
        return false;
    }
}
