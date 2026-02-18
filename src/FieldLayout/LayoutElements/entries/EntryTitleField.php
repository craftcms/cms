<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\entries;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Support\Arr;
use InvalidArgumentException;
use Override;

class EntryTitleField extends TitleField
{
    public bool $mandatory = false;

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

        return ElementHelper::translationDescription($element->getType()->titleTranslationMethod);
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
            return Cp::textareaHtml([
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
