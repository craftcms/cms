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
    /**
     * Checks whether the $function returns an exception and if it is of a certain type.
     *
     * @param        $function The callback function that will throw the exception
     * @param string $requiredException The required exception class
     *
     * @return bool
     */
    public static function ensureException(\Closure $function, $requiredException) : bool
    {
        try {
            $function();
        } catch (\Throwable $exception){
            if ($exception instanceof $requiredException) {
               return true;
            }
        }

        // All went well. Not good.
        return false;
    }
}