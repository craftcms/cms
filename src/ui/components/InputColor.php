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
use craft\ui\ComponentAttributes;

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

    /**
     * @var ComponentAttributes|null Attributes for the containing element;
     */
    public ?ComponentAttributes $containerAttributes = null;

    public function mount(
        string $id = null,
        string $containerId = null,
        bool $autofocus = false,
        array $containerAttributes = [],
    ) {
        $this->id = $id ?? 'color' . mt_rand();
        $this->containerId = $containerId ?? $this->id . '-container';
        $this->hexLabelId = 'hex-' . $id;

        $this->autofocus = $autofocus && !Craft::$app->getRequest()->isMobileBrowser(true);

        $this->containerAttributes = new ComponentAttributes($containerAttributes);
    }
}
