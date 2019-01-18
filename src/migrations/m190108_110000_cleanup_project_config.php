<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\fields\Matrix as MatrixField;
use craft\services\Fields;
use craft\services\Matrix;

/**
 * m190108_110000_cleanup_project_config migration.
 */
class m190108_110000_cleanup_project_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->muteEvents = true;

        // Clean up all the orphan matrix block type from project config or matrix blocks with parent fields that aren't Matrix.
        $matrixBlockTypes = $projectConfig->get(Matrix::CONFIG_BLOCKTYPE_KEY) ?? [];

        foreach ($matrixBlockTypes as $matrixBlockTypeUid => $matrixBlockType) {
            if (empty($matrixBlockType['field'])) {
                $projectConfig->remove(Matrix::CONFIG_BLOCKTYPE_KEY . '.' . $matrixBlockTypeUid);
            } else {
                $fieldUid = $matrixBlockType['field'];
                $field = $fields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY . '.' . $fieldUid);
                if (!$field || !is_array($field) || $field['type'] !== MatrixField::class) {
                    $projectConfig->remove(Matrix::CONFIG_BLOCKTYPE_KEY . '.' . $matrixBlockTypeUid);
                }
            }
        }

        // Clean up all the fields that have no type, such as leftover data for matrix fields that were nested in supertable or the like
        $fields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY) ?? [];

        foreach ($fields as $fieldUid => $field) {
            if (empty($field['type'])) {
                $projectConfig->remove(Fields::CONFIG_FIELDS_KEY . '.' . $fieldUid);
            }
        }

        $projectConfig->muteEvents = false;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190108_110000_cleanup_project_config cannot be reverted.\n";
        return false;
    }
}
