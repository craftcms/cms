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
 * m190108_113000_asset_field_setting_change migration.
 */
class m190108_113000_asset_field_setting_change extends Migration
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
        if (version_compare($schemaVersion, '3.1.11', '>=')) {
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
            if ($fieldData['type'] === Assets::class) {
                if (!empty($fieldData['settings']['singleUploadLocationSource'])) {
                    $fieldData['settings']['singleUploadLocationSource'] = $this->_normalizeSourceKey((string)$fieldData['settings']['singleUploadLocationSource']);
                }

                if (!empty($fieldData['settings']['defaultUploadLocationSource'])) {
                    $fieldData['settings']['defaultUploadLocationSource'] = $this->_normalizeSourceKey((string)$fieldData['settings']['defaultUploadLocationSource']);
                }

                $projectConfig->set(Fields::CONFIG_FIELDS_KEY . '.' . $fieldUid, $fieldData);

                if (!empty($fieldData['settings'])) {
                    $this->update(Table::FIELDS, ['settings' => Json::encode($fieldData['settings'])], ['uid' => $fieldUid]);
                }
            }
        }

        // Do the same for matrix block type fields
        $matrixBlockTypes = $projectConfig->get(Matrix::CONFIG_BLOCKTYPE_KEY) ?? [];

        foreach ($matrixBlockTypes as $matrixBlockTypeUid => $matrixBlockType) {
            $fields = &$matrixBlockType['fields'];

            if (is_array($fields)) {
                foreach ($fields as $fieldUid => &$fieldData) {
                    if ($fieldData['type'] === Assets::class) {
                        if (!empty($fieldData['settings']['singleUploadLocationSource'])) {
                            $fieldData['settings']['singleUploadLocationSource'] = $this->_normalizeSourceKey((string)$fieldData['settings']['singleUploadLocationSource']);
                        }

                        if (!empty($fieldData['settings']['defaultUploadLocationSource'])) {
                            $fieldData['settings']['defaultUploadLocationSource'] = $this->_normalizeSourceKey((string)$fieldData['settings']['defaultUploadLocationSource']);
                        }
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
        echo "m190108_113000_asset_field_setting_change cannot be reverted.\n";
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
