<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

/**
 * SafeLinkTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.2
 * @deprecated 6.0.0 safe link handling is built into {@see \CraftCms\Cms\Support\Facades\Markdown}.
 * @phpstan-ignore trait.unused
 */
trait SafeLinkTrait
{
    /**
     * @var bool Whether `javascript:` links should be parsed
     */
    public $parseJavaScriptLinks = false;

    protected function renderLink($block)
    {
        if (
            !$this->parseJavaScriptLinks &&
            isset($block['url']) &&
            str_starts_with(strtolower($block['url']), 'javascript:')
        ) {
            return $block['orig'] ?? '';
        }

        return parent::renderLink($block);
    }
}
