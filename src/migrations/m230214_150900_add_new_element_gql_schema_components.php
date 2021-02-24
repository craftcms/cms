<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230214_150900_add_new_element_gql_schema_components migration.
 */
class m230214_150900_add_new_element_gql_schema_components extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.6.6', '<')) {
            foreach ($projectConfig->get('graphql.schemas') ?? [] as $schemaUid => $schemaComponents) {
                $scope = $schemaComponents['scope'];
                $scope[] = 'elements.drafts:read';
                $scope[] = 'elements.revisions:read';
                $scope[] = 'elements.inactive:read';

                $projectConfig->set("graphql.schemas.$schemaUid.scope", $scope);
            }
        }

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m230214_150900_add_new_element_gql_schema_components cannot be reverted.\n";
        return false;
    }
}
