<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Support\Facades\DeltaRegistry;

use function CraftCms\Cms\t;

/**
 * Dropdown represents a Dropdown field.
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
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($value, $element, false);
    }

    private function inputHtmlInternal(mixed $value, ?ElementInterface $element, bool $static): string
    {
        /** @var SingleOptionFieldData $value */
        $options = $this->translatedOptions(true, $value, $element);

        $hasBlankOption = false;
        foreach ($options as &$option) {
            if (isset($option['value']) && $option['value'] === '') {
                $option['value'] = '__blank__';
                $hasBlankOption = true;
            }
            if (isset($option['label']) && $option['label'] === '') {
                $option['label'] = ' ';
            }
        }

        if (! $value->valid) {
            if (! $static) {
                DeltaRegistry::setInitialValue($this->handle, $this->encodeValue($value->value));
            }

            $default = $this->defaultValue();
            if ($default !== null) {
                $value = $this->normalizeValue($default, null);
            } else {
                $value = null;

                // Add a blank option to the beginning if one doesn't already exist
                if (! $hasBlankOption) {
                    array_unshift($options, ['label' => ' ', 'value' => '__blank__']);
                }
            }
        }

        return FormFields::selectizeHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $this->encodeValue($value),
            'options' => $options,
            'disabled' => $static,
        ]);
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
