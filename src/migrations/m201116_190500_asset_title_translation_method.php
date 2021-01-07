<?php

namespace craft\migrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\db\Table;
use craft\fieldlayoutelements\AssetTitleField;
use craft\fieldlayoutelements\TitleField;
use craft\services\ProjectConfig;

/**
 * m201116_190500_asset_title_translation_method migration.
 */
class m201116_190500_asset_title_translation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::VOLUMES, 'titleTranslationMethod', $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE)->after('url'));
        $this->addColumn(Table::VOLUMES, 'titleTranslationKeyFormat', $this->text()->after('titleTranslationMethod'));

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.6.0', '<')) {
            foreach ($projectConfig->get('volumes') ?? [] as $volumeUid => $volumeConfig) {
                if ($this->_updateVolumeConfig($volumeConfig)) {
                    $projectConfig->set("volumes.$volumeUid", $volumeConfig);
                }
            }
        }
    }

    /**
     * Updates the title element in a given volume’s field layout.
     *
     * @param array $volumeConfig
     * @return bool Whether the config was updated
     */
    private function _updateVolumeConfig(array &$volumeConfig): bool
    {
        if (isset($volumeConfig['fieldLayouts'])) {
            foreach ($volumeConfig['fieldLayouts'] as &$fieldLayoutConfig) {
                if (isset($fieldLayoutConfig['tabs'])) {
                    foreach ($fieldLayoutConfig['tabs'] as &$tabConfig) {
                        if (isset($tabConfig['elements'])) {
                            foreach ($tabConfig['elements'] as &$elementConfig) {
                                if ($elementConfig['type'] === TitleField::class) {
                                    $elementConfig['type'] = AssetTitleField::class;
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }


    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201116_190500_asset_title_translation_method cannot be reverted.\n";
        return false;
    }
}
