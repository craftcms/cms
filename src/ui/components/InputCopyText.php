<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('input:copytext')]
class InputCopyText extends BaseUiComponent
{
    /**
     * ID of the text input
     *
     * @var string
     */
    public string $id;

    /**
     * ID for the button element.
     *
     * @var string|null
     */
    public ?string $buttonId = null;

    /**
     * Value of text to be copied
     *
     * @var string
     */
    public string $value = 'Copy Text';

    public function prepare(): void
    {
        $this->id = $this->id ?? 'copytext' . mt_rand();
        $this->buttonId = $this->id . '-btn';
    }
}
