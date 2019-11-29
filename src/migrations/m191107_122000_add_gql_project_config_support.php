<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\GqlToken;
use craft\services\Gql;

/**
 * m191107_122000_add_gql_project_config_support migration.
 */
class m191107_122000_add_gql_project_config_support extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $cacheKey = 'migration:add_gql_project_config_support:schemas';
        $cache = Craft::$app->getCache();

        // In case of rollbacks and migration re-runs.
        $cache->delete($cacheKey);

        $this->createTable(Table::GQLTOKENS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'accessToken' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'expiryDate' => $this->dateTime(),
            'lastUsed' => $this->dateTime(),
            'schemaId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        // Add the relation
        $this->addForeignKey(null, Table::GQLTOKENS, 'schemaId', Table::GQLSCHEMAS, 'id', 'SET NULL', null);

        // Get all current schemas
        $allSchemas = (new Query())
            ->select(['*'])
            ->from([Table::GQLSCHEMAS])
            ->indexBy('uid')
            ->all();

        foreach ($allSchemas as &$schema) {
            $schema['isPublic'] = $schema['accessToken'] == GqlToken::PUBLIC_TOKEN;
        }

        $this->dropColumn(Table::GQLSCHEMAS, 'accessToken');
        $this->dropColumn(Table::GQLSCHEMAS, 'enabled');
        $this->dropColumn(Table::GQLSCHEMAS, 'expiryDate');
        $this->dropColumn(Table::GQLSCHEMAS, 'lastUsed');

        $this->addColumn(Table::GQLSCHEMAS, 'isPublic', $this->boolean());

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        // If this is a migration with incoming data, store the memoized data to be accessible later. Wipe the current data for now.
        if (version_compare($schemaVersion, '3.4.1', '>=')) {
            $this->delete(Table::GQLSCHEMAS);

            // Store the existing schemas on the session
            $cache->set($cacheKey, $allSchemas);
            // We're good to split this data into token/schema combos
        } else {
            $projectConfig->muteEvents = true;
            $gqlSchemas = $projectConfig->get(Gql::CONFIG_GQL_SCHEMAS_KEY) ?? [];

            foreach ($allSchemas as $schemaUid => $schema) {
                $this->insert(Table::GQLTOKENS, [
                    'name' => $schema['name'],
                    'accessToken' => $schema['accessToken'],
                    'enabled' => $schema['enabled'],
                    'expiryDate' => $schema['expiryDate'],
                    'lastUsed' => $schema['lastUsed'],
                    'schemaId' => $schema['id'],
                ]);

                // If this was the public schema, set the flag
                if ($schema['accessToken'] == GqlToken::PUBLIC_TOKEN) {
                    $this->update(Table::GQLSCHEMAS, ['isPublic' => true], ['id' => $schema['id']]);
                }

                $gqlSchemas[$schemaUid] = [
                    'name' => $schema['name'],
                    'scope' => Json::decodeIfJson($schema['scope'])
                ];
            }

            $projectConfig->set(Gql::CONFIG_GQL_SCHEMAS_KEY, $gqlSchemas);
            $projectConfig->muteEvents = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191107_122000_add_gql_project_config_support cannot be reverted.\n";
        return false;
    }
}
