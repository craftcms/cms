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
     *
     */
    public function testScalarProperties(): void
    {
        $config = [
            'aliases' => 'foo,bar',
            'allowAdminChanges' => '1',
            'allowUpdates' => '0',
            'baseCpUrl' => '',
            'blowfishHashCost' => 123,
        ];

        Typecast::properties(GeneralConfig::class, $config);

        self::assertSame([
            'aliases' => ['foo', 'bar'],
            'allowAdminChanges' => true,
            'allowUpdates' => false,
            'baseCpUrl' => null,
            'blowfishHashCost' => 123,
        ], $config);
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
