<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\ArrayHelper;
use craft\mail\Mailer;
use craft\test\TestCase;
use craft\test\TestSetup;
use yii\base\InvalidConfigException;

/**
 * Unit tests for App
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AppTest extends TestCase
{
    /**
     * @dataProvider craftAppGetMethodsDataProvider
     * @param string $instance
     * @param array $map
     * @throws InvalidConfigException
     */
    public function testCraftAppGetMethods(string $instance, array $map): void
    {
        $func = $map[0];
        self::assertInstanceOf($instance, Craft::$app->$func());
        self::assertInstanceOf($instance, Craft::$app->get($map[1]));
        // http://www.php.net/manual/en/language.variables.variable.php#example-107
        self::assertInstanceOf($instance, Craft::$app->{$map[1]});
    }

    /**
     * @return array
     */
    public function craftAppGetMethodsDataProvider(): array
    {
        $content = TestSetup::getCraftServiceMap();

        // Dont test mailer. The test get's all fussy about it being a mock.
        /** @noinspection PhpParamsInspection */
        ArrayHelper::removeValue(
            $content, [Mailer::class, ['getMailer', 'mailer']]
        );

        return $content;
    }
}
