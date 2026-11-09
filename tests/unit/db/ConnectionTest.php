<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Craft;
use craft\db\Connection;
use craft\errors\ShellCommandException;
use craft\test\TestCase;

/**
 * Unit tests for the Connection class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.18.10
 */
class ConnectionTest extends TestCase
{
    /**
     * @dataProvider failedBackupCommandDataProvider
     * @param string $backupCommand
     * @param string $password
     * @param string[] $secrets The parts of the password that must not be reported
     */
    public function testFailedBackupDoesNotExposePgPassword(string $backupCommand, string $password, array $secrets): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $originalBackupCommand = $generalConfig->backupCommand;
        $generalConfig->backupCommand = $backupCommand;

        $db = new Connection([
            'dsn' => 'pgsql:host=127.0.0.1;port=5432;dbname=craft_test',
            'username' => 'craft',
            'password' => $password,
        ]);

        try {
            $db->backupTo('/nonexistent/backup.sql');
            self::fail('The backup command should have failed.');
        } catch (ShellCommandException $e) {
            self::assertStringContainsString('PGPASSWORD=', $e->command);
            self::assertStringContainsString('/nonexistent/pg_dump', $e->command);

            foreach ($secrets as $secret) {
                self::assertStringNotContainsString($secret, $e->command);
                self::assertStringNotContainsString($secret, $e->getMessage());
            }
        } finally {
            $generalConfig->backupCommand = $originalBackupCommand;
        }
    }

    /**
     * @return array
     */
    public static function failedBackupCommandDataProvider(): array
    {
        return [
            'default single quotes' => ["PGPASSWORD='{password}' /nonexistent/pg_dump", 'hunter2-secret', ['hunter2-secret']],
            'password with a single quote' => ["PGPASSWORD='{password}' /nonexistent/pg_dump", "before'after-secret", ['before', 'after-secret']],
            'windows style' => ["set PGPASSWORD='{password}' && /nonexistent/pg_dump", 'hunter2-secret', ['hunter2-secret']],
            'double quotes' => ['PGPASSWORD="{password}" /nonexistent/pg_dump', 'hunter2-secret', ['hunter2-secret']],
            'no quotes' => ['PGPASSWORD={password} /nonexistent/pg_dump', 'hunter2-secret', ['hunter2-secret']],
        ];
    }
}
