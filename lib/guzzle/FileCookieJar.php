<?php /** @noinspection PhpUndefinedClassInspection */

namespace GuzzleHttp\Cookie;

use yii\base\NotSupportedException;

class FileCookieJar
{
    /**
     * @throws NotSupportedException
     */
    public function __construct()
    {
        throw new NotSupportedException('FileCookieJar is not supported.');
    }
}
