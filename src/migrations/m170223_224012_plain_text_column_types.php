<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\fields\PlainText;
use craft\helpers\Db;
use craft\helpers\Json;
use yii\db\Schema;

/**
 * m170223_224012_plain_text_column_types migration.
 */
class m170223_224012_plain_text_column_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['type' => PlainText::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            if (empty($settings['maxLength'])) {
                $settings['columnType'] = Schema::TYPE_TEXT;
            } else {
                // This is how Plain Text fields used to automagically determine their column type
                $settings['columnType'] = Db::getTextualColumnTypeByContentLength($settings['maxLength'], $this->db);
            }

            $this->update('{{%fields}}', [
                'settings' => Json::encode($settings)
            ], ['id' => $field['id']]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170223_224012_plain_text_column_types cannot be reverted.\n";
        return false;
    }
}
