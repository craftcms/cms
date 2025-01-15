<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\UrlHelper;
use craft\test\TestCase;
use craft\test\TestSetup;
use UnitTester;
use yii\base\Exception;

/**
 * Unit tests for the Url Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UrlHelperTest extends TestCase
{
    public const ABSOLUTE_URL = 'http://craftcms.com/';
    public const ABSOLUTE_URL_HTTPS = 'https://craftcms.com/';
    public const ABSOLUTE_URL_WWW = 'http://www.craftcms.com/';
    public const ABSOLUTE_URL_HTTPS_WWW = 'https://www.craftcms.com/';
    public const NON_ABSOLUTE_URL = 'craftcms.com/';
    public const NON_ABSOLUTE_URL_WWW = 'www.craftcms.com/';
    public const PROTOCOL_RELATIVE_URL = '//craftcms.com/';
    public const EMAIL_URL = 'mailto:test@abc.com';
    public const TEL_URL = 'tel:+10123456789';
    public const FILE_PATH_1 = 'C:';
    public const FILE_PATH_2 = 'C:\foo\bar.txt';

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var string
     */
    protected string $cpTrigger;

    /**
     * @dataProvider buildQueryDataProvider
     * @param string $expected
     * @param array $params
     */
    public function testBuildQuery(string $expected, array $params): void
    {
        self::assertSame($expected, UrlHelper::buildQuery($params));
    }

    /**
     * @dataProvider isRootRelativeUrlDataProvider
     * @param string $url
     * @param bool $expected
     */
    public function testIsRootRelativeUrl(bool $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::isRootRelativeUrl($url));
    }

    /**
     * @dataProvider isAbsoluteUrlDataProvider
     * @param bool $expected
     * @param string $url
     */
    public function testIsAbsoluteUrl(bool $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::isAbsoluteUrl($url));
    }

    /**
     * @dataProvider isFulUrlDataProvider
     * @param bool $expected
     * @param string $url
     */
    public function testIsFullUrl(bool $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::isFullUrl($url));
    }

    /**
     * Test that control panel URLs are created. We do some hand modification work to construct an 'expected' result based on the cp trigger
     * config variable. We cant do this (yet)(https://github.com/Codeception/Codeception/issues/4087) as the Craft::$app var and thus
     * the cpTrigger variable inst easily accessible in the dataProvider methods.
     *
     * @dataProvider cpUrlCreationDataProvider
     * @param string $expected
     * @param string $path
     * @param array $params
     * @param string $scheme
     */
    public function testCpUrlCreation(string $expected, string $path, array $params, string $scheme = 'https'): void
    {
        $this->tester->mockCraftMethods('request', [
            'getIsSecureConnection' => false,
        ]);

        $expected = $this->_prepExpectedUrl($expected, $scheme);

        self::assertSame($expected, UrlHelper::cpUrl($path, $params, $scheme));

        $this->tester->mockCraftMethods('request', [
            'getIsCpRequest' => true,
        ]);

        self::assertSame($expected, UrlHelper::url($path, $params, $scheme));
    }

    /**
     * @dataProvider urlWithSchemeDataProvider
     * @param string $expected
     * @param string $url
     * @param string $scheme
     */
    public function testUrlWithScheme(string $expected, string $url, string $scheme): void
    {
        self::assertSame($expected, UrlHelper::urlWithScheme($url, $scheme));
    }

    /**
     * @dataProvider urlWithTokenDataProvider
     * @param string $expected
     * @param string $url
     * @param string $token
     */
    public function testUrlWithToken(string $expected, string $url, string $token): void
    {
        Craft::$app->getConfig()->getGeneral()->useSslOnTokenizedUrls = true;
        self::assertSame($expected, UrlHelper::urlWithToken($url, $token));
    }

    /**
     * @dataProvider urlWithParametersDataProvider
     * @param string $expected
     * @param string $url
     * @param array|string $params
     */
    public function testUrlWithParams(string $expected, string $url, array|string $params): void
    {
        self::assertSame($expected, UrlHelper::urlWithParams($url, $params));
    }

    /**
     * @dataProvider stripQueryStringDataProvider
     * @param string $expected
     * @param string $url
     */
    public function testStripQueryString(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::stripQueryString($url));
    }

    /**
     * @dataProvider encodeParamsDataProvider
     */
    public function testEncodeParams(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::encodeParams($url));
    }

    /**
     * @dataProvider encodeUrlDataProvider
     */
    public function testEncodeUrl(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::encodeUrl($url));
    }

    /**
     * Tests the UrlHelper::rootRelativeUrl() method.
     *
     * @dataProvider rootRelativeUrlDataProvider
     * @param string $url
     * @param string $expected
     */
    public function testRootRelativeUrl(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::rootRelativeUrl($url));
    }

    /**
     * Tests the UrlHelper::url() method.
     *
     * @dataProvider urlFunctionDataProvider
     * @param string $expected
     * @param string $path
     * @param array|null $params
     * @param string|null $scheme
     * @param bool|null $showScriptName
     */
    public function testUrlFunction(string $expected, string $path = '', ?array $params = null, ?string $scheme = null, ?bool $showScriptName = null): void
    {
        $scheme = $scheme ?? 'https';
        $expected = $this->_prepExpectedUrl($expected, $scheme);
        self::assertSame($expected, UrlHelper::url($path, $params, $scheme, $showScriptName));
    }

    /**
     * @dataProvider hostInfoDataProvider
     * @param string $expected
     * @param string $url
     */
    public function testHostInfoRetrieval(string $expected, string $url): void
    {
        self::assertSame($expected, UrlHelper::hostInfo($url));
    }

    /**
     *
     */
    public function testSchemeForTokenizedBasedOnConfig(): void
    {
        // Run down the logic to see what we will need to require.
        $config = Craft::$app->getConfig()->getGeneral();

        $config->useSslOnTokenizedUrls = true;
        self::assertSame('https', UrlHelper::getSchemeForTokenizedUrl());

        $config->useSslOnTokenizedUrls = false;
        self::assertSame('http', UrlHelper::getSchemeForTokenizedUrl());
    }

    /**
     * @dataProvider siteUrlDataProvider
     * @param string $expected
     * @param string $path
     * @param array|string|null $params
     * @param string|null $scheme
     * @param int|null $siteId
     */
    public function testSiteUrl(string $expected, string $path, array|string|null $params = null, ?string $scheme = null, ?int $siteId = null): void
    {
        $scheme = $scheme ?? 'https';
        $expected = $this->_prepExpectedUrl($expected, $scheme);
        self::assertSame($expected, UrlHelper::siteUrl($path, $params, $scheme, $siteId));
    }

    /**
     *
     */
    public function testTokenizedSiteUrl(): void
    {
        $this->tester->mockCraftMethods('request', [
            'getToken' => 't0k3n',
        ]);

        $expected = TestSetup::SITE_URL . 'endpoint?token=t0k3n';
        self::assertSame($expected, UrlHelper::url('endpoint'));
        self::assertSame($expected, UrlHelper::siteUrl('endpoint'));
    }

    /**
     * @return void
     */
    public function testActionUrl(): void
    {
        $expected = Craft::getAlias('@web/index.php?p=actions/endpoint');
        self::assertSame($expected, UrlHelper::actionUrl('endpoint'));

        $expected = Craft::getAlias('@web/actions/endpoint');
        self::assertSame($expected, UrlHelper::actionUrl('endpoint', null, null, false));
    }

    /**
     *
     */
    public function testSiteUrlExceptions(): void
    {
        $this->tester->expectThrowable(Exception::class, function() {
            UrlHelper::siteUrl('', null, null, 12892);
        });
    }

    /**
     * @return array
     */
    public static function buildQueryDataProvider(): array
    {
        return [
            ['', []],
            ['', ['foo' => null]],
            ['foo', ['foo' => '']],
            ['foo=0', ['foo' => false]],
            ['foo=1', ['foo' => true]],
            ['foo=1&bar=2', ['foo' => 1, 'bar' => 2]],
            ['foo[0]=1&foo[1]=2', ['foo' => [1, 2]]],
            ['foo[bar]=baz', ['foo[bar]' => 'baz']],
            ['foo[bar]=baz', ['foo' => ['bar' => 'baz']]],
            ['foo=bar%2Bbaz', ['foo' => 'bar+baz']],
            ['foo+bar=baz', ['foo+bar' => 'baz']],
            ['foo=bar%5Bbaz%5D', ['foo' => 'bar[baz]']],
            ['foo={bar}', ['foo' => '{bar}']],
            ['foo[1]=bar', ['foo[1]' => 'bar']],
            ['foo[1][bar]=1&foo[1][baz]=2', ['foo[1][bar]' => 1, 'foo[1][baz]' => 2]],
        ];
    }

    /**
     * @return array
     */
    public static function isAbsoluteUrlDataProvider(): array
    {
        return [
            'absolute-url' => [true, self::ABSOLUTE_URL],
            'absolute-url-https' => [true, self::ABSOLUTE_URL_HTTPS],
            'absolute-url-https-www' => [true, self::ABSOLUTE_URL_HTTPS_WWW],
            'absolute-url-www' => [true, self::ABSOLUTE_URL_WWW],
            'non-url' => [false, self::NON_ABSOLUTE_URL],
            'non-absolute-url-www' => [false, self::NON_ABSOLUTE_URL_WWW],
            'email-url' => [true, self::EMAIL_URL],
            'tel-url' => [true, self::TEL_URL],
            'file-path-1' => [false, self::FILE_PATH_1],
            'file-path-2' => [false, self::FILE_PATH_2],
        ];
    }

    /**
     * @return array
     */
    public static function isFulUrlDataProvider(): array
    {
        return [
            'absolute-url' => [true, self::ABSOLUTE_URL],
            'absolute-url-https' => [true, self::ABSOLUTE_URL_HTTPS],
            'absolute-url-https-www' => [true, self::ABSOLUTE_URL_HTTPS_WWW],
            'absolute-url-www' => [true, self::ABSOLUTE_URL_WWW],
            'root-relative' => [true, '/22'],
            'protocol-relative' => [true, self::PROTOCOL_RELATIVE_URL],
            'mb4-string' => [false, '😀😘'],
            'random-chars' => [false, '!@#$%^&*()<>'],
            'random-string' => [false, 'hello'],
            'non-url' => [false, self::NON_ABSOLUTE_URL],
            'non-absolute-url-www' => [false, self::NON_ABSOLUTE_URL_WWW],
        ];
    }

    /**
     * @return array
     */
    public static function isRootRelativeUrlDataProvider(): array
    {
        return [
            'root-relative-true' => [true, '/22'],
            'protocol-relative' => [false, '//cdn.craftcms.com/22'],
            'absolute-url-https-www' => [false, self::ABSOLUTE_URL_HTTPS_WWW],
            'start-with-param' => [false, '?p=test'],
        ];
    }

    /**
     * @return array
     */
    public static function cpUrlCreationDataProvider(): array
    {
        return [
            'test-empty' => ['{cpUrl}', '', []],
            'test-simple-endpoint' => [
                '{cpUrl}/nav?param1=entry1&param2=entry2',
                'nav',
                ['param1' => 'entry1', 'param2' => 'entry2'],
            ],
            'test-preexisting-endpoints' => [
                '{cpUrl}/nav?param3=entry3&param1=entry1&param2=entry2',
                'nav?param3=entry3',
                ['param1' => 'entry1', 'param2' => 'entry2'],
            ],
            [
                '{cpUrl}/nav?param1=entry1&param2=entry2',
                'nav',
                [
                    'param1' => 'entry1',
                    'param2' => 'entry2',
                ],
                'https',
            ],
            [
                '{siteUrl}?param1=entry1&param2=entry2',
                TestSetup::SITE_URL,
                ['param1' => 'entry1', 'param2' => 'entry2'],
                'https',
            ],
        ];
    }

    /**
     * Tests for UrlHelper::stripQueryString() method
     *
     * @return array
     */
    public static function stripQueryStringDataProvider(): array
    {
        return [
            'invalid-query-string' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW . '&query=string',
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=entry1',
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=entry1?param2=entry2',
            ],
        ];
    }

    /**
     * Tests for UrlHelper::urlWithParams() method
     *
     * @return array
     */
    public static function urlWithParametersDataProvider(): array
    {
        return [
            'with-fragment' => [
                self::ABSOLUTE_URL_HTTPS . '?param1=entry1#some-hashtag',
                self::ABSOLUTE_URL_HTTPS,
                ['param1' => 'entry1', '#' => 'some-hashtag'],
            ],
            'anchor-gets-kept' => [
                self::ABSOLUTE_URL_HTTPS . '?param1=entry1&param2=entry2#anchor',
                self::ABSOLUTE_URL_HTTPS . '#anchor',
                'param1=entry1&param2=entry2',
            ],
            'prev-param-gets-kept' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=entry3&param1=entry1&param2=entry2#anchor',
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=entry3#anchor',
                '?param1=entry1&param2=entry2',
            ],
            '#' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=name&param2=name2#anchor',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2', '#' => 'anchor'],
            ],
            'basic-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2'],
            ],
            'empty-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                [],
            ],
            '4-spaces' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                '    ',
            ],
            'numerical-index-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?0=someparam',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['someparam'],
            ],
            'query-string' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW,
                '?param1=name&param2=name2',
            ],
            'pre-queried-url' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=name3&param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=name3',
                '?param1=name&param2=name2',
            ],
        ];
    }

    /**
     * Tests for UrlHelper::urlWithToken()
     *
     * @return array
     */
    public static function urlWithTokenDataProvider(): array
    {
        $https = true;
        $baseUrl = self::ABSOLUTE_URL_HTTPS;

        return [
            [
                $baseUrl . '?token=value',
                $baseUrl,
                'value',
            ],
            [
                $baseUrl . '?token=value2',
                $baseUrl . '?token=value1',
                'value2',
            ],
            [
                $baseUrl . '?token',
                $baseUrl . '',
                '',
            ],
            'ensure-scheme-is-overridden' => [
                self::ABSOLUTE_URL_HTTPS . '?token=value',
                self::ABSOLUTE_URL,
                'value',
            ],
            'no-protocol' => [
                'craft?token=value',
                'craft',
                'value',
            ],
        ];
    }

    /**
     * Tests for UrlHelper::urlWithScheme()
     *
     * @return array
     */
    public static function urlWithSchemeDataProvider(): array
    {
        return [
            'no-scheme' => [
                'imaurl',
                'imaurl',
                '',
            ],
            'nothing' => [
                '',
                '',
                '',
            ],
            'protocol-relative' => [
                'https://cdn.craftcms.com',
                '//cdn.craftcms.com',
                'https',
            ],
            'php-replace' => [
                str_replace('https://', 'php://', self::ABSOLUTE_URL_HTTPS_WWW),
                self::ABSOLUTE_URL_HTTPS_WWW,
                'php',
            ],
            'ftp-replace' => [
                str_replace('https://', 'ftp://', self::ABSOLUTE_URL_HTTPS),
                self::ABSOLUTE_URL_HTTPS,
                'ftp',
            ],
            'non-valid-protocol' => [
                str_replace('http://', 'walawalabingbang://', self::ABSOLUTE_URL),
                self::ABSOLUTE_URL_HTTPS,
                'walawalabingbang',
            ],
            'www-replace' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                'https',
            ],
            'no-change-needed' => [
                self::ABSOLUTE_URL_HTTPS,
                self::ABSOLUTE_URL_HTTPS,
                'https',
            ],
            'ftp-https' => [
                str_replace('https://', 'sftp://', self::ABSOLUTE_URL_HTTPS_WWW),
                self::ABSOLUTE_URL_HTTPS_WWW,
                'sftp',
            ],
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
    public function encodeUrlDataProvider(): array
    {
        return [
            ['https://domain/fr/offices/gen%C3%AAve', 'https://domain/fr/offices/genêve'],
            ['https://domain/fr/offices/gen%C3%AAve?foo=bar', 'https://domain/fr/offices/genêve?foo=bar'],
            ['https://domain/fr/offices/gen%C3%AAve?foo=bar', 'https://domain/fr/offices/gen%C3%AAve?foo=bar'],
            ['foo+bar', 'foo bar'],
        ];
    }

    /**
     * Tests for UrlHelper::rootRelativeUrl()
     *
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

    /**
     * @return array
     */
    public static function urlFunctionDataProvider(): array
    {
        return [
            'base' => [
                '{siteUrl}endpoint',
                'endpoint',
                null,
                null,
                null,
            ],
            'full-url-scheme' => [
                self::ABSOLUTE_URL_HTTPS,
                self::ABSOLUTE_URL,
                null,
                'https',
            ],
            'scheme-override-param-add' => [
                self::ABSOLUTE_URL_HTTPS . '?param1=entry1&param2=entry2',
                self::ABSOLUTE_URL,
                ['param1' => 'entry1', 'param2' => 'entry2'],
                'https',
            ],
        ];
    }

    public static function hostInfoDataProvider(): array
    {
        return [
            ['https://google.com', 'https://google.com'],
            ['http://facebook.com', 'http://facebook.com'],
            ['ftp://www.craftcms.com', 'ftp://www.craftcms.com/why/craft/is/cool/'],
            ['walawalabingbang://gt.com', 'walawalabingbang://gt.com/'],
            ['sftp://volkswagen', 'sftp://volkswagen////222////222'],
        ];
    }

    public static function siteUrlDataProvider(): array
    {
        return [
            ['{siteUrl}endpoint', 'endpoint'],
            // https://github.com/craftcms/cms/issues/4778
            ['{siteUrl}endpoint?param1=x&param2[0]=y&param2[1]=z', 'endpoint', 'param1=x&param2[]=y&param2[]=z'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->cpTrigger = $generalConfig->cpTrigger;
    }

    /**
     * Swaps URL tokens.
     *
     * @param string $url
     * @param string $scheme
     * @return string
     */
    private function _prepExpectedUrl(string $url, string $scheme): string
    {
        $siteUrl = TestSetup::SITE_URL;
        if ($scheme === 'http') {
            $siteUrl = str_replace('https', 'http', $siteUrl);
        }
        $cpUrl = rtrim($siteUrl, '/') . ":80/$this->cpTrigger";
        return str_replace(['{siteUrl}', '{cpUrl}'], [$siteUrl, $cpUrl], $url);
    }
}
