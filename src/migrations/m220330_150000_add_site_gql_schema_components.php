<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * m220330_150000_add_site_gql_schema_components migration.
 */
class m220330_150000_add_site_gql_schema_components extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0.8', '<')) {
            $sites = $projectConfig->get(ProjectConfig::PATH_SITES) ?? [];
            $siteScopes = array_keys($sites);
            array_walk($siteScopes, function(&$uid) {
                $uid = "sites.$uid:read";
            });

            foreach ($projectConfig->get(ProjectConfig::PATH_GRAPHQL_SCHEMAS) ?? [] as $schemaUid => $schemaComponents) {
                if (isset($schemaComponents['scope'])) {
                    $newScopes = array_merge($schemaComponents['scope'], $siteScopes);
                    $projectConfig->set("graphql.schemas.$schemaUid.scope", $newScopes);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220330_150000_add_site_gql_schema_components cannot be reverted.\n";
        return false;
    }
}
