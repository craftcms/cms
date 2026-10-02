<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

/**
 * Markdown parser that should be used when the content has already been pre-encoded.
 *
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Markdown::parse()} with the `pre-encoded` flavor instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.13
 */
class PreEncodedMarkdown extends BaseMarkdownParser
{
    protected function flavor(): string
    {
        return 'pre-encoded';
    }
}
