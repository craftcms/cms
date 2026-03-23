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
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Markdown::parse()} with the `extra` flavor instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.2
 */
class MarkdownExtra extends BaseMarkdownParser
{
    public bool $codeAttributesOnPre = false;

    protected function flavor(): string
    {
        return 'extra';
    }
}
