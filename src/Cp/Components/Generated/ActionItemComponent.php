<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components\Generated;

use Closure;
use CraftCms\Cms\Cp\Components\ViewComponent;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * A menu entry that renders as a link or a button and can run an
 * action, showing inline loading/success/error feedback.
 *
 * @generated from the `craft-action-item` custom element. Do not edit by hand.
 *           Run `npm run generate:php` in packages/craftcms-cp to regenerate.
 *           Add behavior in the concrete subclass, not here.
 */
abstract class ActionItemComponent extends ViewComponent
{
    protected ?string $icon = null;

    protected ?string $href = null;

    protected bool $disabled = false;

    protected ?string $variant = null;

    protected bool $checked = false;

    protected bool $active = false;

    protected string $type = 'button';

    protected mixed $action = null;

    protected mixed $feedback = null;

    protected int|float $feedbackDuration = 1000;

    protected ?string $confirm = null;

    protected mixed $shortcut = null;

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function href(?string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function disabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function variant(?string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function checked(bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function active(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @param  'button' | 'checkbox'  $type
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function action(mixed $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function feedback(mixed $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function feedbackDuration(int|float $feedbackDuration): static
    {
        $this->feedbackDuration = $feedbackDuration;

        return $this;
    }

    public function confirm(?string $confirm): static
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function shortcut(mixed $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function content(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots[''] = $content;

        return $this;
    }

    public function iconSlot(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['icon'] = $content;

        return $this;
    }

    public function checkmark(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['checkmark'] = $content;

        return $this;
    }

    public function suffix(Closure|Htmlable|Stringable|string $content): static
    {
        $this->slots['suffix'] = $content;

        return $this;
    }

    protected function tagName(): string
    {
        return 'craft-action-item';
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'icon' => $this->icon,
            'href' => $this->href,
            'disabled' => $this->disabled === false ? null : $this->disabled,
            'variant' => $this->variant,
            'checked' => $this->checked === false ? null : $this->checked,
            'active' => $this->active === false ? null : $this->active,
            'type' => $this->type === 'button' ? null : $this->type,
            'action' => $this->action,
            'feedback' => $this->feedback,
            'feedbackDuration' => $this->feedbackDuration === 1000 ? null : $this->feedbackDuration,
            'confirm' => $this->confirm,
            'shortcut' => $this->shortcut,
        ];
    }
}
