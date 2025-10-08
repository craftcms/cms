<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Yiisoft\Translator\Translator;

/**
 * @see \Yiisoft\Translator\Translator
 */
final class Lang extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Translator::class;
    }
}
