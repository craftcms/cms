<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\HtmlAttributes;

#[AsTwigComponent('input:lightswitch')]
class InputLightswitch extends BaseUiComponent
{
    /**
     * @var string|null ID attribute for the input
     */
    public ?string $id = null;

    /**
     * @var string|null ID attribute for the description
     */
    public ?string $descriptionId = null;

    /**
     * @var string State of the switch ('on', 'off', 'indeterminate')
     */
    public string $position = 'off';

    /**
     * @var string Value of the input when position is indeterminate
     */
    public string $indeterminateValue = '-';

    /**
     * @var bool Render small version
     */
    public bool $small = false;

    /**
     * @var bool Reverse toggle direction.
     */
    public bool $reverseToggle = false;

    /**
     * @var bool|null TODO: ????
     */
    public ?bool $toggle = null;

    public string $state = 'idle';

    /**
     * @var string|bool Label for "on" state
     */
    public string|bool $onLabel = false;

    /**
     * @var string|bool Label for "off" state
     */
    public string|bool $offLabel = false;

    /**
     * @var HtmlAttributes|null HTML Attributes for the containing element
     */
    public ?HtmlAttributes $containerAttributes = null;


    /**
     * @var string Value of the input.
     */
    public string $value = '1';

    public function mount(
        string $id = null,
        bool $on = false,
        bool $indeterminate = false,
        bool $disabled = false,
        string $label = null,
        string $onLabel = null,
        string $offLabel = null,
        string $descriptionId = null,
        array $containerAttributes = [],
    ) {
        if ($indeterminate) {
            $this->position = 'indeterminate';
        }

        // On takes precedent over indeterminate
        if ($on) {
            $this->position = 'on';
        }

        if ($disabled) {
            $this->state = 'disabled';
        }

        $this->id = $id ?? 'lightswitch' . mt_rand();
        $this->onLabel = $onLabel ?? $label ?? false;
        $this->offLabel = $offLabel ?? false;
        $this->descriptionId = $descriptionId ?? $this->id . '-desc';
        $this->containerAttributes = new HtmlAttributes($containerAttributes);
    }
}
