<?php /** @noinspection PhpUndefinedClassInspection */

namespace Guzzle\Plugin\Cookie\CookieJar;

use Craft\Exception;

class FileCookieJar
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('FileCookieJar is not supported.');
    }
}