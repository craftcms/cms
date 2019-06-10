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
use craft\test\TestSetup;
use UnitTester;
use yii\base\InvalidConfigException;

/**
 * Unit tests for App
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AppTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    public $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider craftAppGetMethodsDataProvider
     *
     * @param $instance
     * @param $map
     * @throws InvalidConfigException
     */
    public function testCraftAppGetMethods($instance, $map)
    {
        $func = $map[0];
        $this->assertInstanceOf($instance, Craft::$app->$func());
        $this->assertInstanceOf($instance, Craft::$app->get($map[1]));
        // http://www.php.net/manual/en/language.variables.variable.php#example-107
        $this->assertInstanceOf($instance, Craft::$app->{$map[1]});
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function craftAppGetMethodsDataProvider(): array
    {
        $content = TestSetup::getCraftServiceMap();

        // Dont test mailer. The test get's all fussy about it being a mock.
        ArrayHelper::removeValue(
            $content, [Mailer::class, ['getMailer', 'mailer']]
        );

        return $content;
    }
}
