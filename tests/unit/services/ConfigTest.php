<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\services;


use Codeception\Test\Unit;
use Craft;
use craft\services\Config;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author qrazi <qrazi.sivlingworkz@gmail.com>
 * @since 3.3
 */
class ConfigTest extends Unit
{

    public function testAppendConfigAppended()
    {
        $config = $expected = new class(['foo' => 'bar']) extends BaseObject {
            public $foo;
        };

        $sut = Craft::$app->getConfig();

        $actual = $sut->appendConfig('fooConfig', $config);

        $this->assertEquals($expected, $actual);
    }

    public function testAppendConfigAlreadySet()
    {
        $existing_category = Config::CATEGORY_DB;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot override existing config `{$existing_category}`");

        $config = new class(['foo' => 'bar']) extends BaseObject {
            public $foo;
        };

        $sut = Craft::$app->getConfig();

        $sut->appendConfig($existing_category, $config);
    }
}
