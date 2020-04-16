<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\errors\SiteNotFoundException;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\test\Craft as CraftTest;
use UnitTester;
use yii\base\Exception;

/**
 * Unit tests for the Url Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UrlHelperTest extends Unit
{
    const ABSOLUTE_URL = 'http://craftcms.com/';
    const ABSOLUTE_URL_HTTPS = 'https://craftcms.com/';
    const ABSOLUTE_URL_WWW = 'http://www.craftcms.com/';
    const ABSOLUTE_URL_HTTPS_WWW = 'https://www.craftcms.com/';
    const NON_ABSOLUTE_URL = 'craftcms.com/';
    const NON_ABSOLUTE_URL_WWW = 'www.craftcms.com/';
    const PROTOCOL_RELATIVE_URL = '//craftcms.com/';

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    protected $entryScript;

    /**
     * @var string
     */
    protected $entryUrl;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $baseUrlWithScript;

    /**
     * @var string
     */
    protected $cpTrigger;

    /**
     * Replaces the http or https in a url to the $scheme variable.
     *
     * @param $url
     * @param $scheme
     *
     * @return string
     */
    public function urlWithScheme(string $url, string $scheme): string
    {
        // Did they pass the $scheme in with :// or without? If no exists then add it.
        $scheme = strpos('://', $scheme) !== false ? $scheme : $scheme . '://';

        if (strpos($url, 'http://') !== false) {
            $url = str_replace('http://', $scheme, $url);
            return $url;
        }
        if (strpos($url, 'https://') !== false) {
            $url = str_replace('https://', $scheme, $url);
            return $url;
        }

        return $url;
    }

    /**
     * @return string
     */
    public function determineUrlScheme(): string
    {
        return !Craft::$app->getRequest()->getIsConsoleRequest() && Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http';
    }

    /**
     * @dataProvider buildQueryDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testBuildQuery($result, $input)
    {
        $this->assertSame($result, UrlHelper::buildQuery($input));
    }

    /**
     * Tests various methods of the UrlHelper which check that a URL confirms to a specification. I.E. Is it protocol relative or absolute
     *
     * @dataProvider protocolRelativeUrlDataProvider
     * @dataProvider absoluteUrlDataProvider
     * @dataProvider fulUrlDataProvider
     *
     * @param $url
     * @param bool $result
     * @param $method
     */
    public function testIsUrlFunction($url, bool $result, $method)
    {
        $urlHelperResult = UrlHelper::$method($url);
        $this->assertSame($urlHelperResult, $result);
        $this->assertIsBool($urlHelperResult);
    }

    /**
     * Test that control panel URLs are created. We do some hand modification work to construct an 'expected' result based on the cp trigger
     * config variable. We cant do this (yet)(https://github.com/Codeception/Codeception/issues/4087) as the Craft::$app var and thus
     * the cpTrigger variable inst easily accessible in the dataProvider methods.
     *
     * @dataProvider cpUrlCreationDataProvider
     *
     * @param $result
     * @param $inputUrl
     * @param $params
     * @param string $scheme
     */
    public function testCpUrlCreation($result, $inputUrl, $params, $scheme = null)
    {
        $this->tester->mockCraftMethods('request', [
            'getIsSecureConnection' => false,
        ]);

        // Make sure https is enabled for the base url.
        if ($scheme === 'https') {
            $baseUrl = str_replace('http://', 'https://', $this->baseUrlWithScript);
        } else {
            $baseUrl = str_replace('https://', 'http://', $this->baseUrlWithScript);
        }

        $expectedUrl = str_replace(
            ['{baseUrl}', '{cpTrigger}'],
            [$baseUrl, $this->cpTrigger],
            $result
        );

        $this->assertSame($expectedUrl, UrlHelper::cpUrl($inputUrl, $params, $scheme));

        $this->tester->mockCraftMethods('request', [
            'getIsCpRequest' => true,
        ]);

        $this->assertSame($expectedUrl, UrlHelper::url($inputUrl, $params, $scheme));
    }

    /**
     * Tests for various UrlHelper methods that create urls based on a specific format. I.E with token or scheme.
     * The data providers below determine the result and which method is called.
     *
     * @dataProvider urlWithSchemeDataProvider
     * @dataProvider urlWithTokenDataProvider
     * @dataProvider urlWithParametersDataProvider
     * @dataProvider stripQueryStringDataProvider
     *
     * @param bool $result
     * @param      $url
     * @param      $modifier
     * @param      $method
     */
    public function testUrlModifiers($result, $url, $modifier, $method)
    {
        Craft::$app->getConfig()->getGeneral()->useSslOnTokenizedUrls = true;

        $this->assertSame($result, UrlHelper::$method($url, $modifier));
    }

    /**
     * Tests the UrlHelper::rootRelativeUrl() method.
     *
     * @dataProvider rootRelativeUrlDataProvider
     *
     * @param string $url
     * @param string $expected
     */
    public function testRootRelativeUrl(string $url, string $expected)
    {
        $this->assertSame($expected, UrlHelper::rootRelativeUrl($url));
    }

    /**
     * Tests the UrlHelper::url() method.
     *
     * @dataProvider urlFunctionDataProvider
     *
     * @param             $result
     * @param string $path
     * @param null $params
     * @param string|null $scheme
     * @param bool|null $showScriptName
     * @param bool $isNonCompletedUrl
     */
    public function testUrlFunction($result, string $path = '', $params = null, string $scheme = null, bool $showScriptName = null, bool $isNonCompletedUrl = false)
    {
        if ($isNonCompletedUrl === true || !UrlHelper::isAbsoluteUrl($result)) {
            $oldResult = $result;
            $result = $this->baseUrlWithScript . '/' . $oldResult;

            $this->assertSame($result, UrlHelper::url($path, $params, $scheme, false));
            $result = $this->baseUrlWithScript . '?p=' . $oldResult;
        }

        // If no scheme was passed in. We need to set the result to whatever the the url() function will use aswell.
        if ($scheme === null) {
            $scheme = $this->determineUrlScheme();
            $result = $this->urlWithScheme($result, $scheme);
        }

        $this->assertSame($result, UrlHelper::url($path, $params, $scheme, $showScriptName));
    }

    /**
     * Tests that when a $scheme is not defined when creating a url.
     * It uses the below described method to determine the scheme type and adds this to a url.
     */
    public function testAutomaticProtocolType()
    {
        $schemeType = $this->determineUrlScheme();

        // Don't pass in a scheme type. Ensure it determines this itself.
        $result = UrlHelper::url('someendpoint');
        $conformsScheme = (strpos($result, $schemeType) !== false);
        $this->assertTrue($conformsScheme);
    }

    /**
     * @throws SiteNotFoundException
     */
    public function testBaseTesting()
    {
        $baseSiteUrl = Craft::$app->getSites()->getCurrentSite()->getBaseUrl();
        $host = rtrim($this->baseUrl, '/');
        if (mb_strpos($host, '/index.php') !== false) {
            $host = StringHelper::replace($host, '/index.php', '');
        }

        $this->assertSame($baseSiteUrl, UrlHelper::baseUrl());
        $this->assertSame($baseSiteUrl, UrlHelper::baseSiteUrl());
        $this->assertSame($host, UrlHelper::host());

        $this->assertSame('/', UrlHelper::baseCpUrl());
        $this->assertSame('/', UrlHelper::baseRequestUrl());

        // @todo: This right?
        $this->assertSame('', UrlHelper::cpHost());

        Craft::$app->getConfig()->getGeneral()->baseCpUrl = 'https://craftcms.com/test/test';
        $this->assertSame('https://craftcms.com', UrlHelper::cpHost());
    }

    /**
     *
     */
    public function testHostInfoRetrieval()
    {
        $this->assertSame('https://google.com', UrlHelper::hostInfo('https://google.com'));
        $this->assertSame('http://facebook.com', UrlHelper::hostInfo('http://facebook.com'));
        $this->assertSame('ftp://www.craftcms.com', UrlHelper::hostInfo('ftp://www.craftcms.com/why/craft/is/cool/'));
        $this->assertSame('walawalabingbang://gt.com', UrlHelper::hostInfo('walawalabingbang://gt.com/'));
        $this->assertSame('sftp://volkswagen', UrlHelper::hostInfo('sftp://volkswagen////222////222'));

        // If nothing is passed to the hostInfo() your mileage may vary depending on request type. So we need to know what to expect before hand..
        $expectedValue = Craft::$app->getRequest()->getIsConsoleRequest() ? '' : Craft::$app->getRequest()->getHostInfo();
        $this->assertSame($expectedValue, UrlHelper::hostInfo(''));
    }

    /**
     *
     */
    public function testSchemeForTokenizedBasedOnConfig()
    {
        // Run down the logic to see what we will need to require.
        $config = Craft::$app->getConfig()->getGeneral();

        $config->useSslOnTokenizedUrls = true;
        $this->assertSame('https', UrlHelper::getSchemeForTokenizedUrl());

        $config->useSslOnTokenizedUrls = false;
        $this->assertSame('http', UrlHelper::getSchemeForTokenizedUrl());
    }

    /**
     * @dataProvider siteUrlDataProvider
     *
     * @param      $result
     * @param      $path
     * @param null $params
     * @param null $scheme
     * @param null $siteId
     * @throws Exception
     */
    public function testSiteUrl($result, $path, $params = null, $scheme = null, $siteId = null)
    {
        $siteUrl = UrlHelper::siteUrl($path, $params, $scheme, $siteId);
        $this->assertSame($result, $siteUrl);
    }

    /**
     *
     */
    public function testTokenizedSiteUrl()
    {
        $this->tester->mockCraftMethods('request', [
            'getToken' => 't0k3n',
        ]);

        $siteUrl = UrlHelper::url('endpoint');
        $this->assertSame('http://test.craftcms.test/index.php?p=endpoint&token=t0k3n', $siteUrl);

        $siteUrl = UrlHelper::siteUrl('endpoint');
        $this->assertSame('http://test.craftcms.test/index.php?p=endpoint&token=t0k3n', $siteUrl);

        $siteUrl = UrlHelper::actionUrl('endpoint');
        $this->assertSame('http://test.craftcms.test/index.php?p=actions/endpoint', $siteUrl);
    }

    /**
     *
     */
    public function testSiteUrlExceptions()
    {
        $this->tester->expectThrowable(Exception::class, function() {
            UrlHelper::siteUrl('', null, null, 12892);
        });
    }

    /**
     * @return array
     */
    public function buildQueryDataProvider(): array
    {
        return [
            ['', []],
            ['', ['foo' => null]],
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
    public function absoluteUrlDataProvider(): array
    {
        return [
            'absolute-url' => [self::ABSOLUTE_URL, true, 'isAbsoluteUrl'],
            'absolute-url-https' => [self::ABSOLUTE_URL_HTTPS, true, 'isAbsoluteUrl'],
            'absolute-url-https-www' => [self::ABSOLUTE_URL_HTTPS_WWW, true, 'isAbsoluteUrl'],
            'absolute-url-www' => [self::ABSOLUTE_URL_WWW, true, 'isAbsoluteUrl'],
            'non-url' => [self::NON_ABSOLUTE_URL, false, 'isAbsoluteUrl'],
            'non-absolute-url-www' => [self::NON_ABSOLUTE_URL_WWW, false, 'isAbsoluteUrl']
        ];
    }

    /**
     * @return array
     */
    public function fulUrlDataProvider(): array
    {
        return [
            'absolute-url' => [self::ABSOLUTE_URL, true, 'isFullUrl'],
            'absolute-url-https' => [self::ABSOLUTE_URL_HTTPS, true, 'isFullUrl'],
            'absolute-url-https-www' => [self::ABSOLUTE_URL_HTTPS_WWW, true, 'isFullUrl'],
            'absolute-url-www' => [self::ABSOLUTE_URL_WWW, true, 'isFullUrl'],
            'root-relative' => ['/22', true, 'isFullUrl'],
            'protocol-relative' => [self::PROTOCOL_RELATIVE_URL, true, 'isFullUrl'],
            'mb4-string' => ['😀😘', false, 'isFullUrl'],
            'random-chars' => ['!@#$%^&*()<>', false, 'isFullUrl'],
            'random-string' => ['hello', false, 'isFullUrl'],
            'non-url' => [self::NON_ABSOLUTE_URL, false, 'isFullUrl'],
            'non-absolute-url-www' => [self::NON_ABSOLUTE_URL_WWW, false, 'isFullUrl'],
        ];
    }

    /**
     * @return array
     */
    public function protocolRelativeUrlDataProvider(): array
    {
        return [
            'root-relative-true' => ['/22', true, 'isRootRelativeUrl'],
            'protocol-relative' => ['//cdn.craftcms.com/22', false, 'isRootRelativeUrl'],
            'absolute-url-https-www' => [self::ABSOLUTE_URL_HTTPS_WWW, false, 'isRootRelativeUrl'],
            'start-with-param' => ['?p=test', false, 'isRootRelativeUrl']
        ];
    }

    /**
     * @return array
     */
    public function cpUrlCreationDataProvider(): array
    {
        return [
            'test-empty' => ['{baseUrl}?p={cpTrigger}', '', []],
            'test-simple-endpoint' => [
                '{baseUrl}?p={cpTrigger}/nav&param1=entry1&param2=entry2',
                'nav',
                ['param1' => 'entry1', 'param2' => 'entry2']
            ],
            'test-preexisting-endpoints' => [
                '{baseUrl}?p={cpTrigger}/nav&param3=entry3&param1=entry1&param2=entry2',
                'nav?param3=entry3',
                ['param1' => 'entry1', 'param2' => 'entry2']
            ],
            [
                '{baseUrl}?p={cpTrigger}/nav&param1=entry1&param2=entry2',
                'nav',
                [
                    'param1' => 'entry1',
                    'param2' => 'entry2'
                ],
                'https'
            ],
            [
                'https://test.craftcms.test?param1=entry1&param2=entry2',
                'https://test.craftcms.test',
                ['param1' => 'entry1', 'param2' => 'entry2'],
                'https'
            ]
        ];
    }

    /**
     * Tests for UrlHelper::stripQueryString() method
     *
     * @return array
     */
    public function stripQueryStringDataProvider(): array
    {
        return [
            'invalid-query-string' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW . '&query=string',
                null,
                'stripQueryString'
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                null,
                'stripQueryString'
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=entry1',
                null,
                'stripQueryString'
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=entry1?param2=entry2',
                null,
                'stripQueryString'
            ]

        ];
    }

    /**
     * Tests for UrlHelper::urlWithParams() method
     *
     * @return array
     */
    public function urlWithParametersDataProvider(): array
    {
        return [
            'with-fragment' => [
                self::ABSOLUTE_URL_HTTPS . '?param1=entry1#some-hashtag',
                self::ABSOLUTE_URL_HTTPS,
                ['param1' => 'entry1', '#' => 'some-hashtag'],
                'urlWithParams'
            ],
            'anchor-gets-kept' => [
                self::ABSOLUTE_URL_HTTPS . '?param1=entry1&param2=entry2#anchor',
                self::ABSOLUTE_URL_HTTPS . '#anchor',
                'param1=entry1&param2=entry2',
                'urlWithParams'
            ],
            'prev-param-gets-kept' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=entry3&param1=entry1&param2=entry2#anchor',
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=entry3#anchor',
                '?param1=entry1&param2=entry2',
                'urlWithParams'
            ],
            '#' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=name&param2=name2#anchor',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2', '#' => 'anchor'],
                'urlWithParams'
            ],
            'basic-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2'],
                'urlWithParams'
            ],
            'empty-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                [],
                'urlWithParams'
            ],
            '4-spaces' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                '    ',
                'urlWithParams'
            ],
            'numerical-index-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?0=someparam',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['someparam'],
                'urlWithParams'
            ],
            'query-string' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW,
                '?param1=name&param2=name2',
                'urlWithParams'
            ],
            'pre-queried-url' => [
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=name3&param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW . '?param3=name3',
                '?param1=name&param2=name2',
                'urlWithParams'
            ],
        ];
    }

    /**
     * Tests for UrlHelper::urlWithToken()
     *
     * @return array
     */
    public function urlWithTokenDataProvider(): array
    {
        $https = true;
        $baseUrl = self::ABSOLUTE_URL_HTTPS;

        return [
            [
                $baseUrl . '?token=value',
                $baseUrl,
                'value',
                'urlWithToken'
            ],
            [
                $baseUrl . '?token=value2',
                $baseUrl . '?token=value1',
                'value2',
                'urlWithToken'
            ],
            [
                $baseUrl . '?token=',
                $baseUrl . '',
                '',
                'urlWithToken'
            ],
            'ensure-scheme-is-overridden' => [
                $https ? self::ABSOLUTE_URL_HTTPS . '?token=value' : self::ABSOLUTE_URL . '?token=value',
                $https ? self::ABSOLUTE_URL : self::ABSOLUTE_URL_HTTPS,
                'value',
                'urlWithToken'
            ],
            'no-protocol' => [
                'craft?token=value',
                'craft',
                'value',
                'urlWithToken'
            ]
        ];
    }

    /**
     * Tests for UrlHelper::urlWithScheme()
     *
     * @return array
     */
    public function urlWithSchemeDataProvider(): array
    {
        return [
            'no-scheme' => [
                'imaurl',
                'imaurl',
                '',
                'urlWithScheme'
            ],
            'nothing' => [
                '',
                '',
                '',
                'urlWithScheme'
            ],
            'protocol-relative' => [
                'https://cdn.craftcms.com',
                '//cdn.craftcms.com',
                'https',
                'urlWithScheme'
            ],
            'php-replace' => [
                str_replace('https://', 'php://', self::ABSOLUTE_URL_HTTPS_WWW),
                self::ABSOLUTE_URL_HTTPS_WWW,
                'php',
                'urlWithScheme'
            ],
            'ftp-replace' => [
                str_replace('https://', 'ftp://', self::ABSOLUTE_URL_HTTPS),
                self::ABSOLUTE_URL_HTTPS,
                'ftp',
                'urlWithScheme'
            ],
            'non-valid-protocol' => [
                str_replace('http://', 'walawalabingbang://', self::ABSOLUTE_URL),
                self::ABSOLUTE_URL_HTTPS,
                'walawalabingbang',
                'urlWithScheme'
            ],
            'www-replace' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
                'https',
                'urlWithScheme'
            ],
            'no-change-needed' => [
                self::ABSOLUTE_URL_HTTPS,
                self::ABSOLUTE_URL_HTTPS,
                'https',
                'urlWithScheme'
            ],
            'ftp-https' => [
                str_replace('https://', 'sftp://', self::ABSOLUTE_URL_HTTPS_WWW),
                self::ABSOLUTE_URL_HTTPS_WWW,
                'sftp',
                'urlWithScheme'
            ],
        ];
    }

    /**
     * Tests for UrlHelper::rootRelativeUrl()
     *
     * @return array
     */
    public function rootRelativeUrlDataProvider(): array
    {
        return [
            ['', '/'],
            ['foo/bar', '/foo/bar'],
            ['/', '/'],
            ['/foo/bar', '/foo/bar'],
            ['http://test.com', '/'],
            ['http://test.com/', '/'],
            ['http://test.com/foo/bar', '/foo/bar'],
            ['https://test.com', '/'],
            ['https://test.com/', '/'],
            ['https://test.com/foo/bar', '/foo/bar'],
            ['//test.com', '/'],
            ['//test.com/', '/'],
            ['//test.com/foo/bar', '/foo/bar'],
        ];
    }

    /**
     * @return array
     */
    public function urlFunctionDataProvider(): array
    {
        return [
            'base' => ['endpoint', 'endpoint', null, null, null, true],
            'full-url-scheme' => [self::ABSOLUTE_URL_HTTPS, self::ABSOLUTE_URL, null, 'https'],
            'scheme-override' => [self::ABSOLUTE_URL_HTTPS, self::ABSOLUTE_URL, null, 'https'],
            'scheme-override-param-add' => [
                self::ABSOLUTE_URL_HTTPS . '?param1=entry1&param2=entry2',
                self::ABSOLUTE_URL,
                ['param1' => 'entry1', 'param2' => 'entry2'],
                'https'
            ],
        ];
    }

    public function siteUrlDataProvider(): array
    {
        return [
            ['http://test.craftcms.test/index.php?p=endpoint', 'endpoint'],
            // https://github.com/craftcms/cms/issues/4778
            ['http://test.craftcms.test/index.php?p=endpoint&param1=x&param2[0]=y&param2[1]=z', 'endpoint', 'param1=x&param2[]=y&param2[]=z'],
        ];
    }

    public function tokenizedSiteUrlDataProvider(): array
    {
        return [
            ['http://test.craftcms.test/index.php?p=endpoint', 'endpoint'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->cpTrigger = $generalConfig->cpTrigger;
        $configSiteUrl = $generalConfig->siteUrl;

        $craft = $this->getModule(CraftTest::getCodeceptionName());
        $this->entryScript = $craft->_getConfig('entryScript');
        $this->entryUrl = $craft->_getConfig('entryUrl');

        if (!$configSiteUrl) {
            $configSiteUrl = $this->entryUrl;
        }

        $this->baseUrl = $configSiteUrl;

        // Add the entry script. This is for the withScript variable.
        if (strpos($this->entryScript, $configSiteUrl) === false) {
            $configSiteUrl .= $this->entryScript;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->baseUrlWithScript = $configSiteUrl ?: $this->entryScript;
        } else {
            $this->baseUrlWithScript = $configSiteUrl ?: '/';
        }
    }
}
