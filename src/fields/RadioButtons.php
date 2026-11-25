<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\SortableFieldInterface;
use craft\fields\data\SingleOptionFieldData;

/**
 * RadioButtons represents a Radio Buttons field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RadioButtons extends BaseOptionsField implements SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    protected static bool $allowCustomOptions = true;

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
        return Craft::t('app', 'Radio Buttons');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'circle-dot';
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var SingleOptionFieldData $value */
        if (!$value->valid && !$this->customOptions) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        }

        $options = $this->translatedOptions(true, $value, $element);
        if ($this->customOptions && $value->valid) {
            // Add the custom option
            $options[] = [
                'label' => null,
                'value' => '',
                'custom' => true,
            ];
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/radioGroup.twig', [
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $this->encodeValue($value),
            'options' => $options,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Radio Button Options');
    }
}
