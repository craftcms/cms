<?php

namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\UrlHelper;

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
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    const ABSOLUTE_URL = 'https://craftcms.com/';
    const ABSOLUTE_URL_HTTPS = 'https://craftcms.com/';
    const ABSOLUTE_URL_WWW = 'http://www.craftcms.com/';
    const ABSOLUTE_URL_HTTPS_WWW = 'https://www.craftcms.com/';
    const NON_ABSOLUTE_URL = 'craftcms.com/';
    const NON_ABSOLUTE_URL_WWW = 'www.craftcms.com/';
    const PROTOCOL_RELATIVE_URL = '//craftcms.com/';

    /**
     * @dataProvider protocolRelativeUrlData
     * @dataProvider absoluteUrlData
     * @dataProvider fulUrlData
     */
    public function testIsUrlFunction($url, bool $result, $method)
    {
        $urlHelperResult = UrlHelper::$method($url);
        $this->assertSame($urlHelperResult, $result);
        $this->assertInternalType('boolean', $urlHelperResult);
    }

    /**
     * @return array
     */
    public function absoluteUrlData()
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
     * @return array
     */
    public function fulUrlData()
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
     * @return array
     */
    public function protocolRelativeUrlData()
    {
        return [
            'root-relative-true' => [ '/22', true, 'isRootRelativeUrl'],
            'protocol-relative' => [ '//cdn.craftcms.com/22', false, 'isRootRelativeUrl' ],
            'absolute-url-https-www' => [ self::ABSOLUTE_URL_HTTPS_WWW, false, 'isRootRelativeUrl' ]
        ];
    }

    /**
     *
     */
    public function testUrlWithParams()
    {
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['param1' => 'name', 'param2' => 'name2']
            ),
            self::ABSOLUTE_URL_HTTPS_WWW.'?param1=name&param2=name2'
        );

        // Empty array. No modifications alowed.
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                []
            ),
            self::ABSOLUTE_URL_HTTPS_WWW
        );

        // Empty string with spaces. 4 spaces are added and a question mark.
        $this->assertSame(
            self::ABSOLUTE_URL_HTTPS_WWW.'?    ',
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                '    '
            )
        );

        // Non multidim array. Param name with numerical index key.
        $this->assertSame(
            self::ABSOLUTE_URL_HTTPS_WWW.'?0=someparam',
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['someparam']
            )
        );

        // Params via string
        $this->assertSame(
            self::ABSOLUTE_URL_HTTPS_WWW.'?param1=name&param2=name2',
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                '?param1=name&param2=name2'
            )
        );

        // Check on url that already has params.
        $this->assertSame(
            self::ABSOLUTE_URL_HTTPS_WWW.'?param3=name3&param1=name&param2=name2',
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW.'?param3=name3',
                '?param1=name&param2=name2'
            )
        );

    }

    /**
     *
     */
    public function testCpUrlCreation()
    {
        $cpTrigger = \Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $this->assertSame(
            UrlHelper::url($cpTrigger.'/random/endpoint'),
            UrlHelper::cpUrl('random/endpoint')
        );

        // TODO: More scenarios
    }

    /**
     * @throws \craft\errors\SiteNotFoundException
     */
    public function testUrlWithScheme()
    {
        $this->assertEquals('php://www.craftcms.com/', UrlHelper::urlWithScheme(self::ABSOLUTE_URL_HTTPS_WWW, 'php'));
        $this->assertEquals('ftp://craftcms.com/', UrlHelper::urlWithScheme(self::ABSOLUTE_URL_HTTPS, 'ftp'));
        $this->assertEquals('walawalabingbang://craftcms.com/', UrlHelper::urlWithScheme(self::ABSOLUTE_URL, 'walawalabingbang'));
        $this->assertEquals('https://www.craftcms.com/', UrlHelper::urlWithScheme(self::ABSOLUTE_URL_HTTPS_WWW, 'https'));
        $this->assertEquals('https://craftcms.com/', UrlHelper::urlWithScheme(self::ABSOLUTE_URL, 'https'));
        $this->assertEquals('sftp://www.craftcms.com/', UrlHelper::urlWithScheme(self::ABSOLUTE_URL_HTTPS_WWW, 'sftp'));
    }

    public function testHostInfoRetrieval()
    {
        $this->assertSame('https://google.com', UrlHelper::hostInfo('https://google.com'));
        $this->assertSame('http://facebook.com', UrlHelper::hostInfo('http://facebook.com'));
        $this->assertSame('ftp://www.craftcms.com', UrlHelper::hostInfo('ftp://www.craftcms.com/why/craft/is/cool/'));
        $this->assertSame('walawalabingbang://gt.com', UrlHelper::hostInfo('walawalabingbang://gt.com/'));
        $this->assertSame('sftp://volkswagen', UrlHelper::hostInfo('sftp://volkswagen'));

        // If nothing is passed in your mileage may vary depending on request type. So we need to know what to expect before hand..
        $expectedValue = \Craft::$app->getRequest()->getIsConsoleRequest() ? '' : \Craft::$app->getRequest()->getHostInfo();
        $this->assertSame($expectedValue, UrlHelper::hostInfo(''));
    }

    /**
     * @throws \craft\errors\SiteNotFoundException
     */
    public function testUrlCreation()
    {
        $siteUrl = \Craft::$app->getConfig()->getGeneral()->siteUrl;
        $cpTrigger = \Craft::$app->getConfig()->getGeneral()->cpTrigger;

        $ftpUrl = UrlHelper::urlWithScheme($siteUrl, 'ftp');

        $this->assertEquals(
            $ftpUrl.'v1/api?param1=name',
            UrlHelper::url($siteUrl.'v1/api', ['param1' => 'name'], 'ftp', false)
        );
    }

    /**
     * @return bool
     * @throws \craft\errors\SiteNotFoundException
     */
    public function testGetSchemeForTokenUrl()
    {
        $this->assertTrue(in_array(UrlHelper::getSchemeForTokenizedUrl(), ['http', 'https']));

        // Run down the logic to see what we will need to require.
        $useSslOnTokenizedUrls = \Craft::$app->getConfig()->getGeneral()->useSslOnTokenizedUrls;

        // If they've explicitly set `useSslOnTokenizedUrls` to true, require https.
        if ($useSslOnTokenizedUrls === true) {
            $this->assertSame('https', UrlHelper::getSchemeForTokenizedUrl());
            return true;
        }

        // If they've explicitly set `useSslOnTokenizedUrls` to false, require http.
        if ($useSslOnTokenizedUrls === false) {
            $this->assertSame('http', UrlHelper::getSchemeForTokenizedUrl());
            return true;
        }

        // If the siteUrl is https or the current request is https, require https://.
        $scheme = parse_url(UrlHelper::baseSiteUrl(), PHP_URL_SCHEME);
        $request = \Craft::$app->getRequest();
        if (($scheme !== false && strtolower($scheme) === 'https') || (!$request->getIsConsoleRequest() && $request->getIsSecureConnection())) {
            $this->assertSame('https', UrlHelper::getSchemeForTokenizedUrl());
            return true;
        }

        $this->assertSame('http', UrlHelper::getSchemeForTokenizedUrl());
        return true;
    }


}