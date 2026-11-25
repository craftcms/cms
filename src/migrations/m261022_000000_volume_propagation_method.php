<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\enums\PropagationMethod;
use craft\services\ProjectConfig;

/**
 * m261022_000000_volume_propagation_method migration.
 */
class m261022_000000_volume_propagation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Existing volumes on multi-site installs should keep saving assets to all sites.
        // Single-site installs can use custom propagation, as that won't change anything until a site is added.
        $isMultiSite = (new Query())
            ->from(Table::SITES)
            ->where(['dateDeleted' => null])
            ->count() > 1;
        $propagationMethod = $isMultiSite ? PropagationMethod::All : PropagationMethod::Custom;

        if (!$this->db->columnExists(Table::VOLUMES, 'propagationMethod')) {
            $this->addColumn(Table::VOLUMES, 'propagationMethod', $this->string()->defaultValue(PropagationMethod::Custom->value)->notNull()->after('altTranslationKeyFormat'));

            $this->update(Table::VOLUMES, [
                'propagationMethod' => $propagationMethod->value,
            ], '', [], false);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        $volumeConfigs = $projectConfig->get(ProjectConfig::PATH_VOLUMES) ?? [];
        foreach ($volumeConfigs as $uid => $config) {
            if (!isset($config['propagationMethod'])) {
                $config['propagationMethod'] = $propagationMethod->value;
                $projectConfig->set(sprintf('%s.%s', ProjectConfig::PATH_VOLUMES, $uid), $config);
            }
        }

        $projectConfig->muteEvents = $muteEvents;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::VOLUMES, 'propagationMethod');

        return true;
    }
}
