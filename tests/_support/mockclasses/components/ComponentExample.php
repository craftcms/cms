<?php
namespace craftunit\support\mockclasses\components;

use craft\base\ComponentInterface;

class ComponentExample implements ComponentInterface
{
    public static function displayName() : string
    {
        return 'Component example';
    }
}