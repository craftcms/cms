<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\fields\Dropdown;
use craft\helpers\Json;

/**
 * m171016_092553_drop_position_select migration.
 */
class m171016_092553_drop_position_select extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['type' => 'craft\\fields\\PositionSelect'])
            ->all();

        if (empty($fields)) {
            return;
        }

        $labels = [
            'left' => 'Left',
            'center' => 'Center',
            'right' => 'Right',
            'full' => 'Full',
            'drop-left' => 'Drop-left',
            'drop-right' => 'Drop-right',
        ];

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $oldOptions = $settings['options'] ?? [];
            $newOptions = [];

            foreach ($oldOptions as $option) {
                $newOptions[] = [
                    'label' => $labels[$option] ?? $option,
                    'value' => $option,
                    'default' => '',
                ];
            }

            $this->update('{{%fields}}', [
                'type' => Dropdown::class,
                'settings' => Json::encode(['options' => $newOptions]),
            ], [
                'id' => $field['id']
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171016_092553_drop_position_select cannot be reverted.\n";
        return false;
    }
}
