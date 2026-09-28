<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit;

use Craft;
use craft\test\TestCase;
use stdClass;
use yii\base\InvalidConfigException;

/**
 * Unit tests for the Craft class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.4
 */
class CraftTest extends TestCase
{
    /**
     * @dataProvider createObjectDualClassProvider
     */
    public function testCreateObjectDualClass(array $config): void
    {
        $this->expectException(InvalidConfigException::class);
        Craft::createObject($config);
    }

    public static function createObjectDualClassProvider(): array
    {
        return [
            'both set' => [['class' => stdClass::class, '__class' => stdClass::class]],
            'null class' => [['class' => null, '__class' => stdClass::class]],
            'null __class' => [['class' => stdClass::class, '__class' => null]],
        ];
    }

    public function testCreateObjectSingleClass(): void
    {
        self::assertInstanceOf(stdClass::class, Craft::createObject(['class' => stdClass::class]));
        self::assertInstanceOf(stdClass::class, Craft::createObject(['__class' => stdClass::class]));
    }
}
