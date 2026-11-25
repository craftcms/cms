<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db\mysql;

use Craft;
use craft\db\mysql\Schema;
use craft\test\TestCase;

/**
 * Unit tests for the MySQL Schema class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class SchemaTest extends TestCase
{
    /**
     * @dataProvider sslDirectivesFromPrintDefaultsDataProvider
     * @param string $expected
     * @param string $output
     */
    public function testSslDirectivesFromPrintDefaults(string $expected, string $output): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        if (!$schema instanceof Schema) {
            $this->markTestSkipped('The test database isn’t MySQL.');
        }

        self::assertSame($expected, $this->invokeMethod($schema, '_sslDirectivesFromPrintDefaults', [$output]));
    }

    public static function sslDirectivesFromPrintDefaultsDataProvider(): array
    {
        $header = 'mysqldump would have been started with the following arguments:' . PHP_EOL;
        $nl = PHP_EOL;

        return [
            'empty' => ['', ''],
            'header only' => ['', $header],
            'no ssl options' => ['', $header . '--host=localhost --user=root --password=***** --quick'],
            'basic' => [
                "{$nl}ssl-ca=/etc/ssl/ca.pem{$nl}ssl-cert=/etc/ssl/cert.pem{$nl}ssl-key=/etc/ssl/key.pem",
                $header . '--host=db --ssl-ca=/etc/ssl/ca.pem --ssl-cert=/etc/ssl/cert.pem --ssl-key=/etc/ssl/key.pem --quick',
            ],
            'flags and prefixes' => [
                "{$nl}skip-ssl{$nl}disable-ssl-verify-server-cert{$nl}loose-ssl-mode=REQUIRED{$nl}ssl=0",
                $header . '--skip-ssl --disable-ssl-verify-server-cert --loose-ssl-mode=REQUIRED --ssl=0',
            ],
            'verification and tls' => [
                "{$nl}skip-ssl=true{$nl}ssl-verify-server-cert=0{$nl}tls-version=TLSv1.2,TLSv1.3",
                $header . '--skip-ssl=true --ssl-verify-server-cert=0 --tls-version=TLSv1.2,TLSv1.3',
            ],
            'underscores' => [
                "{$nl}ssl-ca=/etc/ssl/ca.pem",
                $header . '--ssl_ca=/etc/ssl/ca.pem',
            ],
            'spaces in values' => [
                "{$nl}ssl-ca=/path/with spaces/ca.pem{$nl}ssl-key=/other path/key.pem",
                $header . '--ssl-ca=/path/with spaces/ca.pem --quick --ssl-key=/other path/key.pem',
            ],
            'windows paths' => [
                "{$nl}ssl-ca=C:\\\\Program Files\\\\MySQL\\\\ca.pem",
                $header . '--ssl-ca=C:\\Program Files\\MySQL\\ca.pem',
            ],
            'quoted values' => [
                "{$nl}ssl-cipher=AES256-SHA",
                $header . '--ssl-cipher="AES256-SHA"',
            ],
            'redacted values' => ['', $header . '--ssl-key=*****'],
            'not ssl' => ['', $header . '--sslx=1 --skip-sslx --tlsfoo=bar'],
        ];
    }
}
