<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\StringHelper;

/**
 * m220314_211928_field_layout_element_uids migration.
 */
class m220314_211928_field_layout_element_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Add UIDs to each of the field layout elements.
        // (No need to update project config; the UIDs only need to be consistent within an edit session.)
        $tabs = (new Query())
            ->select(['id', 'elements'])
            ->from([Table::FIELDLAYOUTTABS])
            ->all();

        foreach ($tabs as $tab) {
            if ($tab['elements']) {
                $elementConfigs = Json::decode($tab['elements']);
                if (is_string($elementConfigs)) {
                    // Maybe it got double-JSON-encoded somehow
                    $elementConfigs = Json::decodeIfJson($elementConfigs);
                }
                $elementConfigs = array_map(function(array $config): array {
                    if (!isset($config['uid'])) {
                        $config['uid'] = StringHelper::UUID();
                    }
                    return $config;
                }, is_array($elementConfigs) ? $elementConfigs : []);
                $this->update(Table::FIELDLAYOUTTABS, [
                    'elements' => Json::encode($elementConfigs),
                ], ['id' => $tab['id']]);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220314_211928_field_layout_element_uids cannot be reverted.\n";
        return false;
    }
}
