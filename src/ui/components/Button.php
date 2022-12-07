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

    public function label(string $value = null): static
    {
        $this->label = $value;
        return $this;
    }

    /**
     * Type of the button
     *
     * @var string
     */
    public string $type = 'button';

    public function type(string $value = 'button'): static
    {
        $this->type = in_array($value, ['submit', 'button']) ? $value : 'button';
        return $this;
    }

    /**
     * Should the spinner be rendered
     *
     * @var bool|null
     */
    public ?bool $spinner = null;

    public function spinner(bool $value = true): static
    {
        $this->spinner = $value;
        return $this;
    }

    /**
     * Mark the button as disabled
     *
     * @var bool|null
     */
    public ?bool $disabled = null;

    public function disabled(bool $value = true): static
    {
        $this->disabled = $value;
        return $this;
    }

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

    public function loading(bool $value = true): static
    {
        $this->loading = $value;

        // Make sure a spinner is rendered for loading
        if ($value) {
            $this->spinner = true;
        }

        return $this;
    }

    /**
     * Submit style button
     *
     * @var bool|null
     */
    public ?bool $submit = null;

    /**
     * @return void
     */
    protected function prepare(): void
    {
        if ($this->loading) {
            $this->addClass('loading');
            $this->spinner = true;
        }

        switch ($this->state) {
            case 'loading':
                $this->loading = true;
                $this->spinner = true;
                break;
            case 'disabled':
                $this->addClass('disabled');
                $this->disabled = true;
                break;
            case 'dashed':
                $this->addClass('dashed');
                $this->dashed = true;
        }

        switch ($this->variant) {
            case 'submit':
                $this->addClass('submit');
                $this->type = 'submit';
                break;
            default:
                $this->type = 'button';
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
