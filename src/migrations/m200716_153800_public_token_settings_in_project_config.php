<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\models\GqlToken;
use craft\services\Gql;

/**
 * m200716_153800_public_token_settings_in_project_config migration.
 */
class m200716_153800_public_token_settings_in_project_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.5.11', '<')) {
            // Default settings, is no public token set.
            $data = [
                'enabled' => false,
                'expiryDate' => null,
            ];

            $publicToken = (new Query())
                ->select([
                    'enabled',
                    'expiryDate',
                ])
                ->from([Table::GQLTOKENS])
                ->where(['accessToken' => GqlToken::PUBLIC_TOKEN])
                ->one();

            // If a public schema token existed, use those settings.
            if ($publicToken) {
                $data['expiryDate'] = $publicToken['expiryDate'] ? DateTimeHelper::toDateTime($publicToken['expiryDate'])->getTimestamp() : null;
                $data['enabled'] = (bool)$publicToken['enabled'];
            }

            // This will ensure that a public schema token is created on sites where it did not exist. It'll just be disabled.
            Craft::$app->getProjectConfig()->set(Gql::CONFIG_GQL_PUBLIC_TOKEN_KEY, $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200716_153800_public_token_settings_in_project_config cannot be reverted.\n";
        return false;
    }
}
