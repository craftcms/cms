<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Entries;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Arr;
use InvalidArgumentException;
use Override;

class EntryTitleField extends TitleField
{
    #[Override]
    public bool $mandatory = false;

    #[Override]
    public bool $requirable = true;

    public function __construct($config = [])
    {
        $this->required = Arr::pull($config, 'required', $this->required);
        unset($config['requirable']);
        parent::__construct($config);
    }

    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['requirable']);
        $fields['required'] = 'required';

        return $fields;
    }

    #[Override]
    public function formNode(?ElementInterface $element, FormContext $context): ?Node
    {
        if ($element !== null && ! $element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        if ($element && ! $element->getType()->hasTitleField) {
            return null;
        }

        $control = $element?->getType()->allowLineBreaksInTitles
            ? Textarea::make($this->name ?? $this->attribute())->rows(2)
            : Text::make($this->name ?? $this->attribute())->inputType($this->inputType ?? 'text');
        $control
            ->value($this->value($element))
            ->mode($this->disabled
                ? ControlMode::Disabled
                : ($this->readonly ? ControlMode::ReadOnly : ControlMode::Editable))
            ->maxLength($this->maxlength)
            ->placeholder($this->placeholder);

        return Field::make()
            ->label($this->showLabel() ? $this->label() : null)
            ->instructions($this->instructionsText($element))
            ->instructionsPosition($this->instructionsPosition)
            ->tip($this->tipText($element))
            ->warning($this->warningText($element))
            ->required($this->required)
            ->layoutUid($this->uid)
            ->width($this->width)
            ->control($control);
    }

    #[Override]
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (! $element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        return $element->getType()->titleTranslationMethod !== TranslationMethod::None;
    }

    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        return $element->getType()->titleTranslationMethod->description();
    }

    #[Override]
    public function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        $entryType = $element->getType();

        if (! $entryType->hasTitleField) {
            return null;
        }

        if ($entryType->allowLineBreaksInTitles) {
            return FormFields::textareaHtml([
                'class' => 'nicetext',
                'id' => $this->id(),
                'describedBy' => $this->describedBy($element, $static),
                'rows' => 2,
                'name' => $this->name ?? $this->attribute(),
                'value' => $this->value($element),
                'maxlength' => $this->maxlength,
                'autofocus' => $this->autofocus,
                'disabled' => $static || $this->disabled,
                'readonly' => $this->readonly,
                'required' => ! $static && $this->required,
                'title' => $this->title,
                'placeholder' => $this->placeholder,
            ]);
        }

        return parent::inputHtml($element, $static);
    }
}
