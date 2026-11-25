<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use craft\fields\Icon;
use craft\test\TestCase;

/**
 * Unit tests for the Icon field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.2
 */
class IconTest extends TestCase
{
    /**
     * @dataProvider normalizeValueDataProvider
     */
    public function testNormalizeValue(?string $expected, string $value): void
    {
        self::assertSame($expected, (new Icon())->normalizeValue($value, null)?->serialize());
    }

    public static function normalizeValueDataProvider(): array
    {
        return [
            'icon name' => ['house', 'house'],
            'custom icon' => ['custom:my-icon', 'custom:my-icon'],
            'url' => [null, 'ftp://example.com/icon.svg'],
            'file path' => [null, '/var/www/icon.svg'],
            'alias' => [null, '@webroot/icon.svg'],
            'relative file path' => [null, 'PRIVATE_ICON'],
            'svg markup' => [null, '<svg xmlns="http://www.w3.org/2000/svg"></svg>'],
        ];
    }
}
