<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 29/09/2018
 * Time: 12:47
 */

namespace app\helpers;


use Codeception\Test\Unit;
use craft\helpers\UrlHelper;

class UrlHelperTest extends Unit
{
    const ABSOLUTE_URL = 'https://craftcms.com/';
    const ABSOLUTE_URL_HTTPS = 'https://craftcms.com/';
    const ABSOLUTE_URL_WWW = 'http://www.craftcms.com/';
    const ABSOLUTE_URL_HTTPS_WWW = 'https://www.craftcms.com/';
    const NON_ABSOLUTE_URL = 'craftcms.com/';
    const NON_ABSOLUTE_URL_WWW = 'www.craftcms.com/';
    const PROTOCOL_RELATIVE_URL = '//craftcms.com/';

    public function testIsAbsoluteUrl()
    {
        $this->assertTrue(UrlHelper::isAbsoluteUrl(self::ABSOLUTE_URL));
        $this->assertTrue(UrlHelper::isAbsoluteUrl(self::ABSOLUTE_URL_HTTPS));
        $this->assertTrue(UrlHelper::isAbsoluteUrl(self::ABSOLUTE_URL_WWW));
        $this->assertTrue(UrlHelper::isAbsoluteUrl(self::ABSOLUTE_URL_HTTPS_WWW));

        $this->assertFalse(UrlHelper::isAbsoluteUrl(self::NON_ABSOLUTE_URL));
        $this->assertFalse(UrlHelper::isAbsoluteUrl(self::NON_ABSOLUTE_URL_WWW));
    }

     public function testIsProtocalRelativeUrl()
    {
        $this->assertTrue(UrlHelper::isProtocolRelativeUrl(self::PROTOCOL_RELATIVE_URL));
    }

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

    public function testCpUrlCreation()
    {
        $cpTrigger = \Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $this->assertSame(
            UrlHelper::url($cpTrigger.'/random/endpoint'),
            UrlHelper::cpUrl('random/endpoint')
        );

        // TODO: More scenarios
    }

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

        // TODO: What if no param is passed in. The method does this variably based on console requests.
        // TODO: How will this work? Should we use if else inside of a test and how are we going ot get the the host info. Preferably via natural vanilla php/
        /*
        if(\Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->assertSame('', UrlHelper::hostInfo('craftcms.com'));
        } else {
            // If its a web request. Make sure that ::hostInfo return the current host
            $this->assertSame($currentHostInfo = UrlHelper::host(), UrlHelper::hostInfo('craftcms.com'));
        }
        */

    }

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

        $this->assertSame('http', UrlHelper::getSchemeForTokenizedUrl('http://'));
        return true;

    }
}