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
        $this->renameTable(Table::GQLSCHEMAS, Table::GQLTOKENS);

        // Add schema ID.
        $this->addColumn(Table::GQLTOKENS, 'schemaId', $this->integer());
        
        // Create the new GraphQL schemas table
        $this->createTable(Table::GQLSCHEMAS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'scope' => $this->text(),
            'isPublic' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        // Add the relation
        $this->addForeignKey(null, Table::GQLTOKENS, 'schemaId', Table::GQLSCHEMAS, 'id', 'SET NULL', null);

        $allTokens = (new Query())
            ->select(['*'])
            ->from([Table::GQLTOKENS])
            ->all();

        $createdSchema = [];

        // For each existing token
        foreach ($allTokens as $token) {
            $schemaUid = StringHelper::UUID();

            // Extract scopes to the new scope table.
            $this->insert(Table::GQLSCHEMAS, [
                'name' => $token['name'],
                'scope' => $token['scope'],
                'uid' => $schemaUid]);
            $schemaId = Craft::$app->getDb()->getLastInsertID();

            $this->update(Table::GQLTOKENS, ['schemaId' => $schemaId], ['id' => $token['id']]);

            // Also, keep track of it because Project Config.
            $createdSchema[$schemaUid] = [
                'name' => $token['name'],
                'scope' => $token['scope'],
            ];
        }

        // Drop the obsolete column.
        $this->dropColumn(Table::GQLTOKENS, 'scope');
        
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.4.1', '>=')) {
            return;
        }

        $projectConfig->muteEvents = true;
        $gqlSchemas = $projectConfig->get(Gql::CONFIG_GQL_SCHEMAS_KEY) ?? [];

        foreach ($createdSchema as $schemaUid => $scope) {
            $gqlSchemas[$schemaUid] = [
                'name' => $scope['name'],
                'scope' => Json::decodeIfJson($scope['scope'])
            ];
        }

        $projectConfig->set(Gql::CONFIG_GQL_SCHEMAS_KEY, $gqlSchemas);

        $projectConfig->muteEvents = false;
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
