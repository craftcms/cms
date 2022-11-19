<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\components;

use craft\base\BaseUiComponent;
use craft\ui\attributes\AsTwigComponent;

#[AsTwigComponent('button')]
class Button extends BaseUiComponent
{
    /**
     * Text label for the button
     *
     * @var string|null
     */
    public ?string $label = null;

    /**
     * Type of the button
     *
     * @var string
     */
    public string $type = 'button';

    /**
     * Variant of the button.
     *
     * @var string
     */
    public string $variant = 'default';

    /**
     * State of the button. Should
     *
     * @var string
     */
    public string $state = 'idle';

    /**
     * Should the spinner be rendered
     *
     * @var bool|null
     */
    public ?bool $spinner = null;

    /**
     * Mark the button as disabled
     *
     * @var bool|null
     */
    public ?bool $disabled = null;

    /**
     * Display dashed style button
     *
     * @var bool|null
     */
    public ?bool $dashed = null;

    /**
     * Display loading button
     *
     * @var bool|null
     */
    public ?bool $loading = null;

    /**
     * Submit style button
     *
     * @var bool|null
     */
    public ?bool $submit = null;

    public function mount(string $type = 'button', string $state = 'idle', string $variant = 'default', bool $submit = false)
    {
        $this->state = $state;
        $this->variant = $variant;
        $this->submit = $submit;
        $this->type = $type;

        if ($this->state === 'loading') {
            $this->loading = true;
            $this->spinner = true;
            $this->label = null;
        }

        if ($this->state === 'disabled') {
            $this->disabled = true;
        }

        if ($this->variant === 'submit' || $type === 'submit' || $submit) {
            $this->type = 'submit';
            $this->submit = true;
        }

        if ($this->variant === 'dashed') {
            $this->dashed = true;
        }
    }

    public function rules(): array
    {
        return [
            [['type'], 'in', 'range' => ['button', 'submit']],
            [['state'], 'in', 'range' => ['idle', 'loading', 'disabled']],
            [['variant'], 'in', 'range' => ['default', 'dashed', 'submit']],
        ];
    }
}
