<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Combobox;

use function CraftCms\Cms\t;

/**
 * Dropdown represents a Dropdown field.
 *
 * @since 6.0.0
 */
class Dropdown extends BaseOptionsField implements InlineEditableFieldInterface, SortableFieldInterface
{
    #[\Override]
    protected static bool $optgroups = true;

    #[\Override]
    protected static bool $optionIcons = true;

    #[\Override]
    protected static bool $optionColors = true;

    #[\Override]
    public static function displayName(): string
    {
        return t('Dropdown');
    }

    #[\Override]
    public static function icon(): string
    {
        return 'ballot-check';
    }

    #[\Override]
    public function getStatus(ElementInterface $element): ?array
    {
        // If the value is invalid and has a default value (which is going to be pulled in via inputHtml()),
        // preemptively mark the field as modified
        /** @var SingleOptionFieldData $value */
        $value = $element->getFieldValue($this->handle);

        if (! $value->valid && $this->defaultValue() !== null) {
            return [
                AttributeStatus::Modified,
                t('This field has been modified.'),
            ];
        }

        return parent::getStatus($element);
    }

    #[\Override]
    public function uiControl(FieldContext $context): Control
    {
        $hasBlankOption = collect($this->translatedOptions(false, $context->value, $context->element))
            ->contains(fn (array $option): bool => ($option['value'] ?? null) === '');
        $value = $this->encodeValue($context->value);

        // Without a blank option, an empty value is the control's own empty state, not an option's __blank__ value
        if ($value === '__blank__' && ! $hasBlankOption) {
            $value = null;
        }

        // A native select can't show icons or colors
        if (collect($this->options)->contains(fn (array $option): bool => ($option['icon'] ?? '') !== '' || ($option['color'] ?? '') !== '')) {
            return Combobox::make($context->path)
                ->requireOptionMatch()
                ->showAllOnEmpty()
                ->clearable(! $hasBlankOption)
                ->options($this->comboboxOptions($context))
                ->value($value);
        }

        $control = parent::uiControl($context);

        if ($control instanceof Choice) {
            $control->value($value);

            // A blank option is encoded as __blank__, so Choice can't tell it supplies its own empty state
            if ($hasBlankOption) {
                $control->withoutPlaceholder();
            }
        }

        return $control;
    }

    /** @return string|list<string> */
    #[\Override]
    protected function encodeValue(MultiOptionsFieldData|OptionData|string|null $value): string|array
    {
        $encValue = parent::encodeValue($value);

        return $encValue === null || $encValue === '' ? '__blank__' : $encValue;
    }

    #[\Override]
    protected function optionsSettingLabel(): string
    {
        return t('Dropdown Options');
    }

    /**
     * @param  array{label:string, value:string, default?:bool|string, icon?:string|null, color?:string|null}  $option
     * @param  list<string>  $selectedValues
     */
    #[\Override]
    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        // special case for blank options, when $value is null
        if ($value === null && $option['value'] === '') {
            if (! $selectedBlankOption) {
                $selectedValues[] = '';
                $selectedBlankOption = true;

                return true;
            }

            return false;
        }

        return in_array($option['value'], $selectedValues, true);
    }
}
