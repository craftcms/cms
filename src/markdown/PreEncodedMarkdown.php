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
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.13
 */
class PreEncodedMarkdown extends Markdown
{
    protected function renderCode($block): string
    {
        $class = isset($block['language']) ? ' class="language-' . $block['language'] . '"' : '';
        return sprintf("<pre><code%s>%s\n</code></pre>\n", $class, $block['content']);
    }

    protected function renderInlineCode($block): string
    {
        return sprintf('<code>%s</code>', $block[1]);
    }
}
