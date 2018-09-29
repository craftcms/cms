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

        // Empty array. No mods
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                []
            ),
            self::ABSOLUTE_URL_HTTPS_WWW
        );

        // Empty string with spaces.
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                '                           '
            ),
            self::ABSOLUTE_URL_HTTPS_WWW
        );

        // Non multidim array. Param name with numerical index key.
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                ['someparam']
            ),
            self::ABSOLUTE_URL_HTTPS_WWW.'?0=someparam'
        );

        // Params via string
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW,
                '?param1=name&param2=name2'
            ),
            self::ABSOLUTE_URL_HTTPS_WWW.'?param1=name&param2=name2'
        );

        // Check on url that has params.
        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW.'?param3=name3',
                '?param1=name&param2=name2'
            ),
            self::ABSOLUTE_URL_HTTPS_WWW.'?param3=name3param1=name&param2=name2'
        );

        $this->assertSame(
            UrlHelper::urlWithParams(
                self::ABSOLUTE_URL_HTTPS_WWW.'#22?param3=name3',
                'param1=name&param2=name2'
            ),
            self::ABSOLUTE_URL_HTTPS_WWW.'#22?param3=name3param1=name&param2=name2'
        );
    }
}