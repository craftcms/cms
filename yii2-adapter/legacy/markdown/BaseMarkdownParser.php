<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

use CraftCms\Cms\Support\Facades\Markdown as MarkdownFacade;

abstract class BaseMarkdownParser
{
    public bool $html5 = true;

    public bool $parseJavaScriptLinks = false;

    abstract protected function flavor(): string;

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Markdown::parse()} instead.
     */
    public function parse(string $markdown): string
    {
        return MarkdownFacade::parse(
            markdown: $markdown,
            flavor: $this->flavor(),
            allowUnsafeLinks: $this->parseJavaScriptLinks,
        );
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Markdown::parseParagraph()} instead.
     */
    public function parseParagraph(string $markdown): string
    {
        return MarkdownFacade::parseParagraph(
            markdown: $markdown,
            flavor: $this->flavor(),
            allowUnsafeLinks: $this->parseJavaScriptLinks,
        );
    }
}
