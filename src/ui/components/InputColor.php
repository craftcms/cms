<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use Craft;
use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;
use craft\validators\ColorValidator;

#[AsTwigComponent('input:color')]
class InputColor extends BaseUiComponent
{
    /**
     * @var string|null ID of the contained input
     */
    public ?string $id = null;

    /**
     * @var string|null ID of the surrounding container. Defaults to $id-container
     */
    public ?string $containerId = null;

    /**
     * @var string|null
     */
    public ?string $hexLabelId = null;

    /**
     * @var string|null name attribute of the input
     */
    public ?string $name = null;

    /**
     * @var string|null Value of the input
     */
    public ?string $value = null;

    /**
     * @var bool Display small variant
     */
    public bool $small = false;

    /**
     * @var bool Autofocus the input
     */
    public bool $autofocus = false;

    /**
     * @var bool Set input to disabled
     */
    public bool $disabled = false;

    /**
     * @var string|null Selector of element labeling input.
     */
    public ?string $labelledBy = null;

    public function prepare(): void
    {
        $this->id = $this->id ?? 'color' . mt_rand();
        $this->containerId = $this->containerId ?? $this->id . '-container';
        $this->hexLabelId = 'hex-' . $this->id;

        $this->autofocus = $this->autofocus && !Craft::$app->getRequest()->isMobileBrowser(true);

        if ($this->value) {
            $this->value = ColorValidator::normalizeColor($this->value);
        }
    }
}
