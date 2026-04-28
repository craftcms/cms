<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

/**
 * Markdown parser
 *
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Markdown::parse()} with the `gfm` or `gfm-comment` flavor instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.2
 */
class GithubMarkdown extends BaseMarkdownParser
{
    public bool $enableNewlines = false;

    protected function flavor(): string
    {
        return $this->enableNewlines ? 'gfm-comment' : 'gfm';
    }
}
