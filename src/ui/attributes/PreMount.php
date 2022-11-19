<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class PreMount
{
    /**
     * @param int $priority If multiple hooks are registered in a component, use to configure
     *                      the order in which they are called (higher called earlier)
     */
    public function __construct(public int $priority = 0)
    {
    }
}
