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
    const ABSOLUTE_URL = 'https://craftcms.com';
    const ABSOLUTE_URL_HTTPS = 'https://craftcms.com';
    const ABSOLUTE_URL_WWW = 'http://www.craftcms.com';
    const ABSOLUTE_URL_HTTPS_WWW = 'https://www.craftcms.com';
    const NON_ABSOLUTE_URL = 'craftcms.com';
    const NON_ABSOLUTE_URL_WWW = 'www.craftcms.com';
    const PROTOCOL_RELATIVE_URL = '//craftcms.com';

    public function testUrlWithParams()
    {
        UrlHelper::urlWithParams();
    }

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
}