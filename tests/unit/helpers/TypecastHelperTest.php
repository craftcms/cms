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
            [GeneralConfig::class, 'allowAdminChanges', true, 'yes'],
            [GeneralConfig::class, 'allowAdminChanges', false, 'no'],
            [GeneralConfig::class, 'allowAdminChanges', true, 'on'],
            [GeneralConfig::class, 'allowAdminChanges', false, 'off'],
            [GeneralConfig::class, 'allowAdminChanges', true, '1'],
            [GeneralConfig::class, 'allowAdminChanges', false, '0'],
            [GeneralConfig::class, 'allowAdminChanges', true, 'true'],
            [GeneralConfig::class, 'allowAdminChanges', false, 'false'],
            [GeneralConfig::class, 'allowAdminChanges', false, ''],
            [GeneralConfig::class, 'allowAdminChanges', false, 'whatever'],
            [GeneralConfig::class, 'baseCpUrl', null, ''],
            [GeneralConfig::class, 'blowfishHashCost', 123, 123],
            [GeneralConfig::class, 'isSystemLive', true, 'yes'],
            [GeneralConfig::class, 'isSystemLive', false, 'no'],
            [GeneralConfig::class, 'isSystemLive', true, 'on'],
            [GeneralConfig::class, 'isSystemLive', false, 'off'],
            [GeneralConfig::class, 'isSystemLive', true, '1'],
            [GeneralConfig::class, 'isSystemLive', false, '0'],
            [GeneralConfig::class, 'isSystemLive', true, 'true'],
            [GeneralConfig::class, 'isSystemLive', false, 'false'],
            [GeneralConfig::class, 'isSystemLive', null, ''],
            [GeneralConfig::class, 'isSystemLive', null, 'whatever'],
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
