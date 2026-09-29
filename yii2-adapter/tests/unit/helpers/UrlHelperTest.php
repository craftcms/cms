<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\UrlHelper;
use craft\test\TestCase;

/**
 * Unit tests for the deprecated Url Helper methods that only exist in the Yii adapter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UrlHelperTest extends TestCase
{
    /**
     * @dataProvider isFullUrlDataProvider
     * @param bool $expected
     * @param string $url
     */
    public function testIsFullUrl(bool $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::isFullUrl($url));
    }

    /**
     * @dataProvider encodeParamsDataProvider
     */
    public function testEncodeParams(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::encodeParams($url));
    }

    /**
     * @dataProvider rootRelativeUrlDataProvider
     * @param string $expected
     * @param string $url
     */
    public function testRootRelativeUrl(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::rootRelativeUrl($url));
    }

    /**
     * @return array
     */
    public static function isFullUrlDataProvider(): array
    {
        return [
            'absolute-url' => [true, 'http://craftcms.com/'],
            'absolute-url-https' => [true, 'https://craftcms.com/'],
            'absolute-url-https-www' => [true, 'https://www.craftcms.com/'],
            'absolute-url-www' => [true, 'http://www.craftcms.com/'],
            'root-relative' => [true, '/22'],
            'protocol-relative' => [true, '//craftcms.com/'],
            'mb4-string' => [false, '😀😘'],
            'random-chars' => [false, '!@#$%^&*()<>'],
            'random-string' => [false, 'hello'],
            'non-url' => [false, 'craftcms.com/'],
            'non-absolute-url-www' => [false, 'www.craftcms.com/'],
        ];
    }

    /**
     * @return array
     */
    public static function encodeParamsDataProvider(): array
    {
        return [
            ['http://example.test', 'http://example.test?'],
            ['http://example.test?foo=bar+baz', 'http://example.test?foo=bar baz'],
            ['http://example.test?foo=bar+baz', 'http://example.test?foo=bar+baz'],
            ['http://example.test?foo=bar+baz#hash', 'http://example.test?foo=bar baz#hash'],
            ['http://example.test?foo=bar%2Bbaz#hash', 'http://example.test?foo=bar%2Bbaz#hash'],
        ];
    }

    /**
     * @return array
     */
    public static function rootRelativeUrlDataProvider(): array
    {
        return [
            ['/', ''],
            ['/foo/bar', 'foo/bar'],
            ['/', '/'],
            ['/foo/bar', '/foo/bar'],
            ['/', 'http://test.com'],
            ['/', 'http://test.com/'],
            ['/foo/bar', 'http://test.com/foo/bar'],
            ['/', 'https://test.com'],
            ['/', 'https://test.com/'],
            ['/foo/bar', 'https://test.com/foo/bar'],
            ['/', '//test.com'],
            ['/', '//test.com/'],
            ['/foo/bar', '//test.com/foo/bar'],
        ];
    }
}
