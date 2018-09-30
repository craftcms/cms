<?php
/**
 * Created by PhpStorm.
 * User: Giel Tettelaar PC
 * Date: 9/30/2018
 * Time: 4:55 PM
 */

namespace craftunit\support\helpers;


class UnitExceptionHandler
{
    public static function ensureException(\Throwable $exception = null, string $class) : bool
    {
        if (!$exception) {
            return false;
        }

        if (!$exception instanceof $class) {
            return false;
        }

        return true;
    }
}