<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use Override;

use function CraftCms\Cms\currentUser;

/**
 * @since 6.0.0
 */
class TextField extends BaseNativeField
{
    /**
     * @var string|null The input type
     */
    public ?string $inputType = null;

    /**
     * @var string|bool|null The input’s `autocomplete` attribute value.
     *
     * This can be set to `true` (`"on"`), `false` (`"off"`), or any other allowed `autocomplete` value.
     * `null` is treated as `false`.
     */
    public string|bool|null $autocomplete = false;

    /**
     * @var string|string[]|null The input’s `class` attribute value.
     */
    public string|array|null $class = null;

    /**
     * @var int|null The input’s `size` attribute value.
     */
    public ?int $size = null;

    /**
     * @var string|null The input’s `name` attribute value.
     *
     * If this is not set, [[attribute()]] will be used by default.
     */
    public ?string $name = null;

    /**
     * @var int|null The input’s `maxlength` attribute value.
     */
    public ?int $maxlength = null;

    /**
     * @var bool Whether the input should get an `autofocus` attribute.
     */
    public bool $autofocus = false;

    /**
     * @var bool Whether the input should support autocorrect.
     */
    public bool $autocorrect = true;

    /**
     * @var bool Whether the input should support auto-capitalization.
     */
    public bool $autocapitalize = true;

    /**
     * @var bool Whether the input should get a `disabled` attribute.
     */
    public bool $disabled = false;

    /**
     * @var bool Whether the input should get a `readonly` attribute.
     */
    public bool $readonly = false;

    /**
     * @var string|null The input’s `title` attribute value.
     */
    public ?string $title = null;

    /**
     * @var string|null The input’s `placeholder` attribute value.
     */
    public ?string $placeholder = null;

    /**
     * @var int|null The input’s `step` attribute value.
     */
    public ?int $step = null;

    /**
     * @var int|null The input’s `min` attribute value.
     */
    public ?int $min = null;

    /**
     * @var int|null The input’s `max` attribute value.
     */
    public ?int $max = null;

    #[Override]
    public function fields(): array
    {
        // Don't include the value
        return Arr::except(parent::fields(), ['value']);
    }

    #[Override]
    protected function uiControl(FieldLayoutElementContext $context): ?Control
    {
        // Subclasses set per-element tokens, such as the current user's `email`, through inputAttributes(),
        // and some of them require an element.
        $autocomplete = ($context->element ? $this->inputAttributes($context->element)['autocomplete'] ?? null : null)
            ?? $this->autocomplete;

        return Text::make($this->name ?? $this->attribute())
            ->inputType($this->inputType ?? 'text')
            ->autocomplete($autocomplete ?? false)
            ->autocorrect($this->autocorrect)
            ->autocapitalize($this->autocapitalize)
            ->value($this->value($context->element))
            ->mode($this->disabled
                ? ControlMode::Disabled
                : ($this->readonly ? ControlMode::ReadOnly : ControlMode::Editable))
            ->maxLength($this->maxlength)
            ->placeholder($this->placeholder)
            ->step($this->step)
            ->min($this->min)
            ->max($this->max);
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return FormFields::textHtml([
            'type' => $this->inputType,
            'autocomplete' => $this->autocomplete,
            'class' => $this->class,
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'size' => $this->size,
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus,
            'autocorrect' => $this->autocorrect,
            'autocapitalize' => $this->autocapitalize,
            'disabled' => $static || $this->disabled,
            'readonly' => $this->readonly,
            'required' => ! $static && $this->required,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,
            'inputAttributes' => $this->inputAttributes($element, $static),
        ]);
    }

    /**
     * Returns text input attributes.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     * @return array<string, string|array<string, string>>
     */
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    #[Override]
    protected function baseInputName(): string
    {
        return $this->name ?? parent::baseInputName();
    }

    #[Override]
    protected function errorKey(): string
    {
        return $this->name ?? parent::errorKey();
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (currentUser()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
