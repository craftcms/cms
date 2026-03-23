<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

use CraftCms\Cms\Support\Facades\Deprecator;
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
        $this->logIgnoredSettings();

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
        $this->logIgnoredSettings();

        return MarkdownFacade::parseParagraph(
            markdown: $markdown,
            flavor: $this->flavor(),
            allowUnsafeLinks: $this->parseJavaScriptLinks,
        );
    }

    protected function logIgnoredSettings(): void
    {
        if (!$this->html5) {
            Deprecator::log(
                sprintf('%s::$html5', static::class),
                sprintf('`%s::$html5` is deprecated and ignored. HTML5 output is always used.', static::class),
            );
        }
    }
}
