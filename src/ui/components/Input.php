<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;

class Input extends BaseUiComponent
{
    /**
     * Type of form control.
     *
     * @var string
     */
    public string $type = 'text';

    /**
     * Render the input as disabled.
     *
     * @var bool|null
     */
    public ?bool $disabled = null;

    /**
     * Size of the input. When not provided, the `fullwidth` class will be added.
     *
     * @var int|null
     */
    public ?int $size = null;
}
