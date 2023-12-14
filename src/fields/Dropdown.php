<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\SortableFieldInterface;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Cp;

/**
 * Dropdown represents a Dropdown field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Dropdown extends BaseOptionsField implements SortableFieldInterface
{
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
    public static function valueType(): string
    {
        return sprintf('\\%s', SingleOptionFieldData::class);
    }

    /**
     * @inheritdoc
     */
    protected bool $optgroups = true;

    public function getStatus(ElementInterface $element): ?array
    {
        // If the value is invalid and has a default value (which is going to be pulled in via inputHtml()),
        // preemptively mark the field as modified
        /** @var SingleOptionFieldData $value */
        $value = $element->getFieldValue($this->handle);

        if (!$value->valid && $this->defaultValue() !== null) {
            return [
                Element::ATTR_STATUS_MODIFIED,
                Craft::t('app', 'This field has been modified.'),
            ];
        }

        return parent::getStatus($element);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
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
                $option['value'] = '__BLANK__';
                $hasBlankOption = true;
            }
        }

        if (!$value->valid) {
            if (!$static) {
                Craft::$app->getView()->setInitialDeltaValue($this->handle, $this->encodeValue($value->value));
            }
            $default = $this->defaultValue();

            if ($default !== null) {
                $value = $this->normalizeValue($this->defaultValue());
            } else {
                $value = null;

                // Add a blank option to the beginning if one doesn't already exist
                if (!$hasBlankOption) {
                    array_unshift($options, ['label' => '', 'value' => '__BLANK__']);
                }
            }
        }

        $encValue = $this->encodeValue($value);
        if ($encValue === null || $encValue === '') {
            $encValue = '__BLANK__';
        }

        return Cp::selectizeHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $encValue,
            'options' => $options,
            'disabled' => $static,
        ]);
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
