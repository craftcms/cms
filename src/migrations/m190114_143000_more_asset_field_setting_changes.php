<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Assets;
use craft\helpers\Json;
use craft\services\Fields;
use craft\services\Matrix;

/**
 * m190114_143000_more_asset_field_setting_changes migration.
 */
class m190114_143000_more_asset_field_setting_changes extends Migration
{
    /**
     * @var array List of volume UIDs keyed by folder UIds.
     */
    private $_volumesByFolderUids = [];

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.17', '>=')) {
            return;
        }

        $this->_volumesByFolderUids = (new Query())
            ->select(['folders.uid folderUid', 'volumes.uid volumeUid'])
            ->from(['{{%volumes}} volumes'])
            ->innerJoin(['{{%volumefolders}} folders'], '[[volumes.id]] = [[folders.volumeId]]')
            ->pairs();

        $projectConfig = Craft::$app->getProjectConfig();

        // Get the field data from the project config
        $fields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY) ?? [];
        $projectConfig->muteEvents = true;

        foreach ($fields as $fieldUid => $fieldData) {
            if ($fieldData['type'] === Assets::class && !empty($fieldData['settings']['sources'])) {
                $sources = &$fieldData['settings']['sources'];

                if (is_array($sources)) {
                    foreach ($sources as &$source) {
                        $source = $this->_normalizeSourceKey($source);
                    }
                    unset($source);
                }

                $projectConfig->set(Fields::CONFIG_FIELDS_KEY . '.' . $fieldUid, $fieldData);

                if (!empty($fieldData['settings'])) {
                    $this->update(Table::FIELDS, ['settings' => Json::encode($fieldData['settings'])], ['uid' => $fieldUid]);
                }
            }
        }

        // Do the same for matrix block type fields
        $matrixBlockTypes = $projectConfig->get(Matrix::CONFIG_BLOCKTYPE_KEY, true) ?? [];

        foreach ($matrixBlockTypes as $matrixBlockTypeUid => $matrixBlockType) {
            $fields = &$matrixBlockType['fields'];
            
            if (!is_array($fields)) {
                continue;
            }

            foreach ($fields as $fieldUid => &$fieldData) {
                if ($fieldData['type'] === Assets::class && !empty($fieldData['settings']['sources'])) {
                    $sources = &$fieldData['settings']['sources'];

                    if (is_array($sources)) {
                        foreach ($sources as &$source) {
                            $source = $this->_normalizeSourceKey($source);
                        }
                        unset($source);
                    }
                }
            }

            $projectConfig->set(Matrix::CONFIG_BLOCKTYPE_KEY . '.' . $matrixBlockTypeUid, $matrixBlockType);

            if (!empty($fieldData['settings'])) {
                $this->update(Table::FIELDS, ['settings' => Json::encode($fieldData['settings'])], ['uid' => $fieldUid]);
            }
        }

        $projectConfig->muteEvents = false;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190114_143000_more_asset_field_setting_changes cannot be reverted.\n";
        return false;
    }

    /**
     * Normalize a folder:UID key to volume:UID
     *
     * @param string $sourceKey
     * @return string
     */
    private function _normalizeSourceKey(string $sourceKey): string
    {
        if (empty($sourceKey) || strpos($sourceKey, 'folder:') !== 0) {
            return $sourceKey;
        }

        $parts = explode(':', $sourceKey);
        $folderUid = $parts[1];

        return array_key_exists($folderUid, $this->_volumesByFolderUids) ? 'volume:' . $this->_volumesByFolderUids[$folderUid] : $sourceKey;
    }
}
