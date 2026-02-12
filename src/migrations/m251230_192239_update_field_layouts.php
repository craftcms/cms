<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;
use craft\records\FieldLayout as FieldLayoutRecord;

/**
 * m251230_192239_update_field_layouts migration.
 */
class m251230_192239_update_field_layouts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        /** @var FieldLayoutRecord[] $dbLayouts */
        $dbLayouts = FieldLayoutRecord::find()->all();

        foreach ($dbLayouts as $layout) {
            $config = $layout->config;
            if (is_string($config)) {
                $config = Json::decode($config);
            }
            if (!empty($config) && $this->updateLayoutConfig($config)) {
                $layout->config = $config;
                $layout->save(false, ['config']);
            }
        }

        $pc = Craft::$app->getProjectConfig();
        $muteEvents = $pc->muteEvents;
        $pc->muteEvents = true;

        $pcLayoutArrays = $pc->find(
            fn(array $item, string $path) => !empty($item['fieldLayouts']) && is_array($item['fieldLayouts']),
        );

        foreach ($pcLayoutArrays as $path => $configs) {
            $updated = false;
            foreach ($configs['fieldLayouts'] as &$config) {
                if (is_array($config) && $this->updateLayoutConfig($config)) {
                    $updated = true;
                }
            }
            unset($config);
            if ($updated) {
                $pc->set($path, $configs);
            }
        }

        $pc->muteEvents = $muteEvents;

        return true;
    }

    private function updateLayoutConfig(array &$config): bool
    {
        if (empty($config['tabs'])) {
            return false;
        }

        $updateCardView = empty($config['cardView']);
        $updateThumbField = empty($config['thumbFieldKey']);

        if (!$updateCardView && !$updateThumbField) {
            return false;
        }

        $updated = false;

        if ($updateCardView) {
            $config['cardView'] = [];
        }

        foreach ($config['tabs'] as &$tab) {
            if (empty($tab['elements'])) {
                continue;
            }

            foreach ($tab['elements'] as &$element) {
                if (isset($element['uid'])) {
                    if (isset($element['includeInCards'])) {
                        if ($updateCardView && $element['includeInCards']) {
                            $config['cardView'][] = "layoutElement:{$element['uid']}";
                        }
                        unset($element['includeInCards']);
                        $updated = true;
                    }

                    if (isset($element['providesThumbs'])) {
                        if ($updateThumbField && $element['providesThumbs']) {
                            $config['thumbFieldKey'] = "layoutElement:{$element['uid']}";
                        }
                        unset($element['providesThumbs']);
                        $updated = true;
                    }
                }
            }
        }
        unset($tab);

        return $updated;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251230_192239_update_field_layouts cannot be reverted.\n";
        return false;
    }
}
