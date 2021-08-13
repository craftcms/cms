<?php

namespace craft\migrations;

use Craft;
use craft\authentication\type\mfa\AuthenticatorCode;
use craft\authentication\type\mfa\WebAuthn;
use craft\authentication\type\Password;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Authentication;

/**
 * m210813_102900_initial_auth_chain migration.
 */
class m210813_102900_initial_auth_chain extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $authChains = [
                Authentication::CP_AUTHENTICATION_CHAIN => [
                    'branches' => [
                        [
                            'title' => 'WebAuthn',
                            'steps' => [
                                [
                                    'choices' => [
                                        [
                                            'type' => WebAuthn::class
                                        ],
                                    ],
                                    'required' => true
                                ],
                            ],
                        ],
                        [
                            'title' => 'Optional 2FA',
                            'steps' => [
                                [
                                    'choices' => [
                                        [
                                            'type' => Password::class
                                        ],
                                    ],
                                    'required' => true
                                ],
                                [
                                    'choices' => [
                                        [
                                            'type' => AuthenticatorCode::class
                                        ],
                                    ],
                                    'required' => false
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $projectConfig->set(Authentication::CONFIG_AUTH_CHAINS, $authChains);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210724_180756_rename_source_cols cannot be reverted.\n";
        return false;
    }
}
