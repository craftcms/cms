<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

use CraftCms\Cms\Support\Facades\Deprecator;

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

    protected function logIgnoredSettings(): void
    {
        parent::logIgnoredSettings();

        if ($this->codeAttributesOnPre) {
            Deprecator::log(
                sprintf('%s::$codeAttributesOnPre', static::class),
                sprintf('`%s::$codeAttributesOnPre` is deprecated and ignored. Code block attributes use the CommonMark defaults.', static::class),
            );
        }
    }
}
