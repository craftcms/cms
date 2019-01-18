<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Categories;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

/**
 * m170217_044740_category_branch_limits migration.
 */
class m170217_044740_category_branch_limits extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $categoryFields = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FIELDS])
            ->where([
                'and',
                ['type' => Categories::class],
                ['not', ['settings' => null]]
            ])
            ->all($this->db);

        foreach ($categoryFields as $field) {
            $settings = Json::decode($field['settings']);
            if (array_key_exists('limit', $settings)) {
                $settings['branchLimit'] = ArrayHelper::remove($settings, 'limit');
                $this->update(Table::FIELDS, [
                    'settings' => Json::encode($settings)
                ], ['id' => $field['id']]);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170217_044740_category_branch_limits cannot be reverted.\n";

        return false;
    }
}
