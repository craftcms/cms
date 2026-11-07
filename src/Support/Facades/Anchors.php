<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string parseHtml(string $html, array|string $tags = 'h1,h2,h3', string|null $language = null, bool $lowercase = false)
 * @method static string generateAnchorName(string $heading, string|null $language = null, bool $lowercase = false)
 *
 * @see \CraftCms\Cms\View\Anchors
 */
class Anchors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\Anchors::class;
    }
}
