<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\App;
use craft\mail\transportadapters\Sendmail;
use craft\models\MailSettings;
use craft\test\TestCase;
use yii\base\Component;

/**
 * Unit tests for the App Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AppHelperTest extends TestCase
{
    /**
     * @dataProvider configsDataProvider
     * @param string $method
     * @param array $desiredConfig
     */
    public function testConfigIndexes(string $method, array $desiredConfig): void
    {
        $config = App::$method();

        self::assertFalse($this->_areKeysMissing($config, $desiredConfig));

        // Make sure we aren't passing in anything unknown or invalid.
        self::assertTrue(class_exists($config['class']));

        // Make sure its a component
        self::assertContains(Component::class, class_parents($config['class']));
    }

    /**
     * Mailer config is sourced from Laravel mail config/runtime.
     */
    public function testMailerConfigIndexes(): void
    {
        config()->set('mail.from', [
            'address' => 'mailer@craft.test',
            'name' => 'Mailer Name',
        ]);
        config()->set('mail.reply_to', [
            'address' => 'reply@craft.test',
            'name' => null,
        ]);

        $mailSettings = new MailSettings([
            'fromEmail' => 'legacy@craft.test',
            'fromName' => 'Legacy Name',
            'replyToEmail' => 'legacy-reply@craft.test',
            'transportType' => Sendmail::class,
        ]);
        $result = App::mailerConfig($mailSettings);

        self::assertFalse($this->_areKeysMissing($result, ['class', 'messageClass', 'from', 'replyTo', 'template', 'transport']));

        // Make sure its a component
        self::assertContains(Component::class, class_parents($result['class']));
        self::assertTrue(class_exists($result['class']));
        self::assertSame(['mailer@craft.test' => 'Mailer Name'], $result['from']);
        self::assertSame('reply@craft.test', $result['replyTo']);
        self::assertSame(app('mail.manager')->mailer()->getSymfonyTransport(), $result['transport']);
    }

    /**
     * @return array
     */
    public static function configsDataProvider(): array
    {
        return [
            ['assetManagerConfig', ['class', 'basePath', 'baseUrl', 'fileMode', 'dirMode', 'appendTimestamp']],
            ['dbConfig', ['class', 'dsn', 'password', 'username', 'charset', 'tablePrefix', 'schemaMap', 'commandMap', 'attributes', 'enableSchemaCache']],
            ['mutexConfig', ['class', 'fileMode', 'dirMode']],
            ['webRequestConfig', ['class', 'enableCookieValidation', 'cookieValidationKey', 'enableCsrfValidation', 'enableCsrfCookie', 'csrfParam', ]],
            ['cacheConfig', ['class', 'keyPrefix', 'defaultDuration']],
            ['sessionConfig', ['class', 'as session', 'flashParam']],
            ['userConfig', ['class', 'identityClass', 'enableAutoLogin', 'autoRenewCookie', 'loginUrl', 'authTimeout']],
        ];
    }

    /**
     * @param array $configArray
     * @param array $desiredSchemaArray
     * @return bool
     */
    private function _areKeysMissing(array $configArray, array $desiredSchemaArray): bool
    {
        foreach ($desiredSchemaArray as $desiredSchemaItem) {
            if (!array_key_exists($desiredSchemaItem, $configArray)) {
                return true;
            }
        }

        return false;
    }
}
