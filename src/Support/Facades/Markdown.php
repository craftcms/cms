<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Markdown\MarkdownOptions;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void extend(string $name, callable $flavor)
 * @method static bool has(string $name)
 * @method static string parse(string $markdown, ?string $flavor = null, bool $allowUnsafeLinks = false)
 * @method static string parseParagraph(string $markdown, ?string $flavor = null, bool $allowUnsafeLinks = false)
 * @method static string convert(string $markdown, MarkdownOptions $options)
 *
 * @see \CraftCms\Cms\Markdown\Markdown
 */
class Markdown extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Markdown\Markdown::class;
    }
}
