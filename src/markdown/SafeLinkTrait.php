<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

use craft\helpers\StringHelper;

/**
 * SafeLinkTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.2
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
            StringHelper::startsWith($block['url'], 'javascript:', false)
        ) {
            return $block['orig'] ?? '';
        }

        return parent::renderLink($block);
    }
}
