<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
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
        $fieldsService = Craft::$app->getFields();
        $matrixService = Craft::$app->getMatrix();

        $fieldsService->ignoreProjectConfigChanges = true;
        $matrixService->ignoreProjectConfigChanges = true;

        $projectConfig = Craft::$app->getProjectConfig();

        // Clean up all the orphan matrix block type from project config
        $matrixBlockTypes = $projectConfig->get(Matrix::CONFIG_BLOCKTYPE_KEY) ?? [];

        foreach ($matrixBlockTypes as $matrixBlockTypeUid => $matrixBlockType) {
            if (empty($matrixBlockType['field'])) {
                $projectConfig->remove(Matrix::CONFIG_BLOCKTYPE_KEY . '.' . $matrixBlockTypeUid);
            }
        }

        // Clean up all the fields that have no type, such as leftover data for matrix fields that were nested in supertable or the like
        $fields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY) ?? [];

        foreach ($fields as $fieldUid => $field) {
            if (empty($field['type'])) {
                $projectConfig->remove(Fields::CONFIG_FIELDS_KEY . '.' . $fieldUid);
            }
        }

        $fieldsService->ignoreProjectConfigChanges = false;
        $matrixService->ignoreProjectConfigChanges = false;
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
