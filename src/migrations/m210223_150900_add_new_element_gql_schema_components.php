<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210223_150900_add_new_element_gql_schema_components migration.
 */
class m210223_150900_add_new_element_gql_schema_components extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.6.5', '<')) {
            foreach ($projectConfig->get('graphql.schemas') ?? [] as $schemaUid => $schemaComponents) {
                if (isset($schemaComponents['scope'])) {
                    $scope = $schemaComponents['scope'];
                    $scope[] = 'elements.drafts:read';
                    $scope[] = 'elements.revisions:read';
                    $scope[] = 'elements.inactive:read';

                    $projectConfig->set("graphql.schemas.$schemaUid.scope", $scope);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210223_150900_add_new_element_gql_schema_components cannot be reverted.\n";
        return false;
    }
}
