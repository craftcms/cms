<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\enums\AttributeStatus;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Cp;

/**
 * Dropdown represents a Dropdown field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Dropdown extends BaseOptionsField implements SortableFieldInterface, InlineEditableFieldInterface
{
    /**
     * @inheritdoc
     */
    protected static bool $optgroups = true;

    /**
     * @inheritdoc
     */
    protected static bool $optionIcons = true;

    /**
     * @inheritdoc
     */
    protected static bool $optionColors = true;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Dropdown');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'ballot-check';
    }

    /**
     * @inheritdoc
     */
    public function getStatus(ElementInterface $element): ?array
    {
        // If the value is invalid and has a default value (which is going to be pulled in via inputHtml()),
        // preemptively mark the field as modified
        /** @var SingleOptionFieldData $value */
        $value = $element->getFieldValue($this->handle);

        if (!$value->valid && $this->defaultValue() !== null) {
            return [
                AttributeStatus::Modified,
                Craft::t('app', 'This field has been modified.'),
            ];
        }

        return parent::getStatus($element);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($value, $element, false);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return $this->inputHtmlInternal($value, $element, true);
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

        if (!$value->valid) {
            if (!$static) {
                Craft::$app->getView()->setInitialDeltaValue($this->handle, $this->encodeValue($value->value));
            }

            $default = $this->defaultValue();
            if ($default !== null) {
                $value = $this->normalizeValue($default, null);
            } else {
                $value = null;

                // Add a blank option to the beginning if one doesn't already exist
                if (!$hasBlankOption) {
                    array_unshift($options, ['label' => ' ', 'value' => '__blank__']);
                }
            }
        }

        return Cp::selectizeHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $this->encodeValue($value),
            'options' => $options,
            'disabled' => $static,
        ]);
    }

    protected function encodeValue(MultiOptionsFieldData|OptionData|string|null $value): string|array|null
    {
        $encValue = parent::encodeValue($value);
        return $encValue === null || $encValue === '' ? '__blank__' : $encValue;
    }

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Dropdown Options');
    }

    /**
     * @inheritdoc
     */
    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        // special case for blank options, when $value is null
        if ($value === null && $option['value'] === '') {
            if (!$selectedBlankOption) {
                $selectedValues[] = '';
                $selectedBlankOption = true;
                return true;
            }

            return false;
        }

        return in_array($option['value'], $selectedValues, true);
    }
}
