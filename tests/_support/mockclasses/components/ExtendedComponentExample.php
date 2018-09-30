<?php
/**
 * Created by PhpStorm.
 * User: Giel Tettelaar PC
 * Date: 9/30/2018
 * Time: 5:05 PM
 */

namespace craftunit\support\mockclasses\components;

class ExtendedComponentExample extends ComponentExample
{
    public static function displayName(): string
    {
        return 'Extended component example';
    }
}