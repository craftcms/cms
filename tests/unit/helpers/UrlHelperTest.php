<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;


use Codeception\Test\Unit;
use Craft;
use craft\db\Query;
use craft\helpers\UrlHelper;
use craftunit\fixtures\SitesFixture;
use UnitTester;
use yii\base\Exception;

/**
 * Unit tests for the Url Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class UrlHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected $entryScript;
    protected $entryUrl;
    protected $baseUrl;
    protected $baseUrlWithScript;
    protected $cpTrigger;

    protected function _before()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->cpTrigger = $generalConfig->cpTrigger;
        $configSiteUrl = $generalConfig->siteUrl;

        $craft = $this->getModule(\craft\test\Craft::class);
        $this->entryScript = $craft->_getConfig('entryScript');
        $this->entryUrl = $craft->_getConfig('entryUrl');

        if (!$configSiteUrl) {
            $configSiteUrl = $this->entryUrl;
        }

        $this->baseUrl = $configSiteUrl;
        // Add the entry script. This  is for the withScript variable.
        if (strpos($this->entryScript, $configSiteUrl) === false) {
            $configSiteUrl .= $this->entryScript;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->baseUrlWithScript = $configSiteUrl ?: $this->entryScript;
        } else {
            $this->baseUrlWithScript = $configSiteUrl ?: '/';
        }
    }

    const ABSOLUTE_URL = 'http://craftcms.com/';
    const ABSOLUTE_URL_HTTPS = 'https://craftcms.com/';
    const ABSOLUTE_URL_WWW = 'http://www.craftcms.com/';
    const ABSOLUTE_URL_HTTPS_WWW = 'https://www.craftcms.com/';
    const NON_ABSOLUTE_URL = 'craftcms.com/';
    const NON_ABSOLUTE_URL_WWW = 'www.craftcms.com/';
    const PROTOCOL_RELATIVE_URL = '//craftcms.com/';

    /**
     * Tests various methods of the UrlHelper which check that a URL confirms to a specification. I.E. Is it protocol relative or absolute
     *
     * @dataProvider protocolRelativeUrlData
     * @dataProvider absoluteUrlData
     * @dataProvider fulUrlData
     * @param $url
     * @param bool $result
     * @param $method
     */
    public function testIsUrlFunction($url, bool $result, $method)
    {
        $urlHelperResult = UrlHelper::$method($url);
        $this->assertSame($urlHelperResult, $result);
        $this->assertInternalType('boolean', $urlHelperResult);
    }

    /**
     * Add tests for whether urls are qualified as absolute.
     * @return array
     */
    public function absoluteUrlData(): array
    {
        return [
            'absolute-url' => [ self::ABSOLUTE_URL, true, 'isAbsoluteUrl' ],
            'absolute-url-https' => [ self::ABSOLUTE_URL_HTTPS, true, 'isAbsoluteUrl' ],
            'absolute-url-https-www' => [ self::ABSOLUTE_URL_HTTPS_WWW, true, 'isAbsoluteUrl' ],
            'absolute-url-www' => [ self::ABSOLUTE_URL_WWW, true, 'isAbsoluteUrl' ],
            'non-url' => [self::NON_ABSOLUTE_URL, false, 'isAbsoluteUrl'],
            'non-absolute-url-www' => [ self::NON_ABSOLUTE_URL_WWW, false, 'isAbsoluteUrl' ]
        ];
    }

    /**
     * Add tests for whether URLS are qualified as a full url.
     * @return array
     */
    public function fulUrlData(): array
    {
        return [
            'absolute-url' => [ self::ABSOLUTE_URL, true, 'isFullUrl' ],
            'absolute-url-https' => [ self::ABSOLUTE_URL_HTTPS, true, 'isFullUrl' ],
            'absolute-url-https-www' => [ self::ABSOLUTE_URL_HTTPS_WWW, true, 'isFullUrl' ],
            'absolute-url-www' => [ self::ABSOLUTE_URL_WWW, true, 'isFullUrl' ],
            'root-relative' => [ '/22', true, 'isFullUrl' ],
            'protocol-relative' => [ self::PROTOCOL_RELATIVE_URL, true, 'isFullUrl' ],
            'mb4-string' => [ '😀😘', false, 'isFullUrl' ],
            'random-chars' => [ '!@#$%^&*()<>', false, 'isFullUrl' ],
            'random-string' => ['hello', false, 'isFullUrl'],
            'non-url' => [self::NON_ABSOLUTE_URL, false, 'isFullUrl'],
            'non-absolute-url-www' => [ self::NON_ABSOLUTE_URL_WWW, false, 'isFullUrl' ],
        ];
    }

    /**
     * Add tests for whether URLS are qualified as root relative
     * @return array
     */
    public function protocolRelativeUrlData(): array
    {
        return [
            'root-relative-true' => [ '/22', true, 'isRootRelativeUrl'],
            'protocol-relative' => [ '//cdn.craftcms.com/22', false, 'isRootRelativeUrl' ],
            'absolute-url-https-www' => [ self::ABSOLUTE_URL_HTTPS_WWW, false, 'isRootRelativeUrl' ]
        ];
    }

    /**
     * Test that adding params to urls works under various circumstances
     * @dataProvider urlWithParamsData()
     * @param $result
     * @param $url
     * @param $params
*/
    public function testUrlWithParams($result, $url, $params)
    {
        $this->assertSame($result, UrlHelper::urlWithParams($url, $params));
    }

    public function urlWithParamsData(): array
    {
        return [
            '#' => [
                self::ABSOLUTE_URL_HTTPS_WWW.'?param1=name&param2=name2#anchor',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2', '#' => 'anchor']
            ],
            'basic-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW.'?param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2']
            ],
            'empty-array' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW,
               []
            ],
            '4-spaces' => [
                self::ABSOLUTE_URL_HTTPS_WWW.'?    ',
                self::ABSOLUTE_URL_HTTPS_WWW,
                '    '
            ],
            'numerical-index-array'  => [
                self::ABSOLUTE_URL_HTTPS_WWW.'?0=someparam',
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['someparam']
            ],
            'query-string' => [
                self::ABSOLUTE_URL_HTTPS_WWW.'?param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW,
                '?param1=name&param2=name2'
            ],
            'pre-queried-url' => [
                self::ABSOLUTE_URL_HTTPS_WWW.'?param3=name3&param1=name&param2=name2',
                self::ABSOLUTE_URL_HTTPS_WWW.'?param3=name3',
                '?param1=name&param2=name2'
            ],
    ];
    }


    /**
     * Test that CP Urls are created. We do some hand modification work to construct an 'expected' result based on the cp trigger
     * config variable. We cant do this (yet)(https://github.com/Codeception/Codeception/issues/4087) as the Craft::$app var and thus
     * the cpTrigger variable inst easily accessible in the dataProvider methods.
     *
     * @dataProvider cpUrlCreationData
     * @param $result
     * @param $inputUrl
     * @param $params
     * @param string $scheme
*/
    public function testCpUrlCreation($result, $inputUrl, $params, $scheme = 'https')
    {
        // Make sure https is enabled for the base url.
        if ($scheme === 'https') {
            $baseUrl = str_replace('http://', 'https://', $this->baseUrlWithScript);
        } else {
            $baseUrl = str_replace('https://', 'http://', $this->baseUrlWithScript);
        }

        $expectedUrl = $baseUrl.'?p='.$this->cpTrigger.''.$result.'';

        $this->assertSame(
            $expectedUrl,
            UrlHelper::cpUrl($inputUrl, $params, $scheme)
        );
    }

    /**
     * @return array
     */
    public function cpUrlCreationData(): array
    {
        return [
            'test-empty' => ['', '', []],
            'test-simple-endpoint' => [
                '/nav&param1=entry1&param2=entry2',
                'nav',
                ['param1' => 'entry1', 'param2' => 'entry2']
            ],
            'test-preexisting-endpoints' => [
                '/nav&param3=entry3&param1=entry1&param2=entry2',
                'nav?param3=entry3',
                ['param1' => 'entry1', 'param2' => 'entry2']
            ],
            [
                '/nav&param1=entry1&param2=entry2',
                'nav',
                [
                    'param1' => 'entry1',
                    'param2' => 'entry2'
                ],
                'https'
            ],
            'test-url-gets-ignored' => [
                '/https://test.craftcms.dev&param1=entry1&param2=entry2',
                'https://test.craftcms.dev',
                ['param1' => 'entry1', 'param2' => 'entry2'],
                'https'
            ]
        ];
    }

    /**
     * Tests for various UrlHelper methods that create urls based on a specific format. I.E with token or scheme.
     * The data providers below determine the result and which method is called.
     * @dataProvider urlWithSchemeProvider
     * @dataProvider urlWithTokenProvider
     * @dataProvider urlWithParamsProvider
     * @dataProvider stripQueryStringProvider
     * @param bool $result
     * @param      $url
     * @param $modifier
     * @param      $method
*/
    public function testUrlModifiers($result, $url, $modifier, $method)
    {
        Craft::$app->getConfig()->getGeneral()->useSslOnTokenizedUrls = true;

        $this->assertSame($result, UrlHelper::$method($url, $modifier));
    }

    /**
     * Tests for UrlHelper::stripQueryString() method
     * @return array
     */
    public function stripQueryStringProvider(): array
    {
        return [
            'invalid-query-string' => [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW.'&query=string',
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
                self::ABSOLUTE_URL_HTTPS_WWW.'?param1=entry1',
                null,
                'stripQueryString'
            ],
            [
                self::ABSOLUTE_URL_HTTPS_WWW,
                self::ABSOLUTE_URL_HTTPS_WWW.'?param1=entry1?param2=entry2',
                null,
                'stripQueryString'
            ]

        ];
    }

    /**
     * Tests for UrlHelper::urlWithParams() method
     * @return array
     */
    public function urlWithParamsProvider(): array
    {
        return [
            'with-fragment' => [
                self::ABSOLUTE_URL_HTTPS.'?param1=entry1#some-hashtag',
                self::ABSOLUTE_URL_HTTPS,
                ['param1' => 'entry1', '#' => 'some-hashtag'],
                'urlWithParams'
            ],
            [
                self::ABSOLUTE_URL_HTTPS.'?param1=entry1',
                self::ABSOLUTE_URL_HTTPS,
                ['param1' => 'entry1'],
                'urlWithParams'
            ],
            [
                self::ABSOLUTE_URL_HTTPS.'?param1=entry1&param2=entry2',
                self::ABSOLUTE_URL_HTTPS,
                ['param1' => 'entry1', 'param2' => 'entry2'],
                'urlWithParams'
            ],
            [
                self::ABSOLUTE_URL_HTTPS.'?param1=entry1&param2=entry2',
                self::ABSOLUTE_URL_HTTPS,
                'param1=entry1&param2=entry2',
                'urlWithParams'
            ],
            'anchor-gets-kept' => [
                self::ABSOLUTE_URL_HTTPS.'#anchor?param1=entry1&param2=entry2',
                self::ABSOLUTE_URL_HTTPS.'#anchor',
                'param1=entry1&param2=entry2',
                'urlWithParams'
            ],
            'prev-param-gets-kept' => [
                self::ABSOLUTE_URL_HTTPS_WWW.'#anchor?param3=entry3&param1=entry1&param2=entry2',
                self::ABSOLUTE_URL_HTTPS_WWW.'#anchor?param3=entry3',
                '?param1=entry1&param2=entry2',
                'urlWithParams'
            ],
        ];
    }

    /**
     * Tests for UrlHelper::urlWithToken()
     * @return array
     */
    public function urlWithTokenProvider(): array
    {
        $https = true;
        $baseUrl = self::ABSOLUTE_URL_HTTPS;

        return [
            [
                $baseUrl.'?token=value',
                $baseUrl,
                'value',
                'urlWithToken'
            ],
            [
                $baseUrl.'?token=value&token=value',
                $baseUrl.'?token=value',
                'value',
                'urlWithToken'
            ],
            [
                $baseUrl.'?token=',
                $baseUrl.'',
                '',
                'urlWithToken'
            ],
            'ensure-scheme-is-overridden' => [
                $https ? self::ABSOLUTE_URL_HTTPS.'?token=value' : self::ABSOLUTE_URL.'?token=value',
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
     * @return array
     */
    public function urlWithSchemeProvider(): array
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
     * Tests the UrlHelper::url() method.
     *
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
            $result = $this->baseUrl.$oldResult;

            $this->assertSame($result, UrlHelper::url($path, $params, $scheme, false));
            $result = $this->baseUrlWithScript.'?p='.$oldResult;
        }

        // If no scheme was passed in. We need to set the result to whatever the the url() function will use aswell.
        if ($scheme === null) {
            $scheme = $this->determineUrlScheme();
            $result = $this->urlWithScheme($result, $scheme);
        }

        $this->assertSame($result, UrlHelper::url($path, $params, $scheme, $showScriptName));
    }

    public function urlFunctionDataProvider(): array
    {
        return [
            'base' => ['endpoint', 'endpoint',  null,  null, null, true],
            'full-url-scheme' => [self::ABSOLUTE_URL_HTTPS, self::ABSOLUTE_URL,  null,  'https'],
            'full-url-scheme' => [self::ABSOLUTE_URL_HTTPS, self::ABSOLUTE_URL,  null,  'https'],
            'scheme-override' => [self::ABSOLUTE_URL_HTTPS, self::ABSOLUTE_URL,  null,  'https'],
            'scheme-override-param-add' => [
                self::ABSOLUTE_URL_HTTPS.'?param1=entry1&param2=entry2',
                self::ABSOLUTE_URL,
                ['param1'=> 'entry1', 'param2'=>'entry2'],
                'https'
            ],

        ];
    }

    /**
     * Replaces the http or https in a url to the $scheme variable.
     * @param $url
     * @param $scheme
     * @return string
     */
    public function urlWithScheme(string $url, string $scheme) : string
    {
        // Did they pass the $scheme in with :// or without? If no exists then add it.
        $scheme = strpos('://', $scheme) !== false ? $scheme : $scheme.'://';

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

    public function determineUrlScheme(): string
    {
        return !Craft::$app->getRequest()->getIsConsoleRequest() && Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http';
    }

    /**
     * Tests that when a $scheme is not defined when creating a url.
     * It uses the below described method to determine the scheme type and adds this to a url.
     */
    public function testAutomaticProtocolType()
    {
        $schemeType = $this->determineUrlScheme();

        // Dont pass in a scheme type. Ensure it determines this itself.
        $result = UrlHelper::url('someendpoint');
        $conformsScheme = (strpos($result, $schemeType) !== false);
        $this->assertTrue($conformsScheme);
    }

    public function testBaseTesting()
    {
        $this->assertSame($this->baseUrl, UrlHelper::baseUrl());
        $this->assertSame($this->baseUrl, UrlHelper::baseSiteUrl());
        $this->assertSame(rtrim($this->baseUrl, '/'), UrlHelper::host());

        $this->assertSame('/', UrlHelper::baseCpUrl());
        $this->assertSame('/', UrlHelper::baseRequestUrl());
        $this->assertSame('', UrlHelper::cpHost());
    }

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

    public function testSchemeForTokenizedBasedOnConfig()
    {
        // Run down the logic to see what we will need to require.
        $config =  Craft::$app->getConfig()->getGeneral();

        $config->useSslOnTokenizedUrls = true;
        $this->assertSame('https', UrlHelper::getSchemeForTokenizedUrl());

        $config->useSslOnTokenizedUrls = false;
        $this->assertSame('http', UrlHelper::getSchemeForTokenizedUrl());
    }

    /**
     * @dataProvider siteUrlData
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
    public function siteUrlData(): array
    {
        return [
            ['http://test.craftcms.dev/index.php?p=endpoint', 'endpoint'],
        ];
    }
    public function testSiteUrlExceptions()
    {
        $this->tester->expectThrowable(Exception::class, function () {
            UrlHelper::siteUrl('', null, null, 12892);
        });
    }
}
