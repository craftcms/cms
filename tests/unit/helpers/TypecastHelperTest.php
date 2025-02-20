<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\config\GeneralConfig;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\Typecast;
use craft\test\TestCase;
use crafttests\unit\helpers\typecast\EnumModel;
use crafttests\unit\helpers\typecast\Suit;
use DateTime;

/**
 * Unit tests for the Typecast helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class TypecastHelperTest extends TestCase
{
    /**
     * @dataProvider scalarPropertiesDataProvider
     *
     * @param string $class
     * @param string $property
     * @param mixed $expected
     * @param mixed $value
     */
    public function testScalarProperties(string $class, string $property, mixed $expected, mixed $value): void
    {
        $config = [
            $property => $value,
        ];

        Typecast::properties($class, $config);

        self::assertSame([
            $property => $expected,
        ], $config);
    }

    public static function scalarPropertiesDataProvider(): array
    {
        return [
            [GeneralConfig::class, 'aliases', ['foo', 'bar'], 'foo,bar'],
            [GeneralConfig::class, 'allowAdminChanges', true, '1'],
            [GeneralConfig::class, 'allowUpdates', false, '0'],
            [GeneralConfig::class, 'baseCpUrl', null, ''],
            [GeneralConfig::class, 'blowfishHashCost', 123, 123],
            [GeneralConfig::class, 'maxUploadFileSize', 123, '123'],
            [GeneralConfig::class, 'maxUploadFileSize', '123abc', '123abc'],
        ];
    }

    /**
     *
     */
    public function testDateTimeProperties(): void
    {
        $now = DateTimeHelper::now();

        $config = [
            'postDate' => $now->format(DateTime::ATOM),
            'expiryDate' => '',
        ];

        Typecast::properties(Entry::class, $config);

        self::assertInstanceOf(DateTime::class, $config['postDate']);
        self::assertSame($now->getTimestamp(), $config['postDate']->getTimestamp());
        self::assertNull($config['expiryDate']);
    }

    /**
     *
     */
    public function testEnumProperties(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('PHP 8.1+ only');
        }

        $config = [
            'suit' => 'H',
            'anotherSuit' => Suit::Hearts,
            'nullableSuit' => '',
        ];

        Typecast::properties(EnumModel::class, $config);

        self::assertSame([
            'suit' => Suit::Hearts,
            'anotherSuit' => Suit::Hearts,
            'nullableSuit' => null,
        ], $config);
    }
}
