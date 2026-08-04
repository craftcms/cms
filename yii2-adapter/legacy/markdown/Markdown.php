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
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Markdown::parse()} instead.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.2
 */
class Markdown extends BaseMarkdownParser
{
    protected function flavor(): string
    {
        return 'original';
    }
}
