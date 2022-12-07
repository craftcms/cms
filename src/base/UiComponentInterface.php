<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

interface UiComponentInterface
{
    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string;
}
