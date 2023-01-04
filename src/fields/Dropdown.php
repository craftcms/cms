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
use craft\helpers\ArrayHelper;

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

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var SingleOptionFieldData $value */
        $options = $this->translatedOptions(true);

        if (!$value->valid) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, $this->encodeValue($value->value));
            $value = null;

            // Add a blank option to the beginning if one doesn't already exist
            if (!ArrayHelper::contains($options, function($option) {
                return isset($option['value']) && $option['value'] === '';
            })) {
                array_unshift($options, ['label' => '', 'value' => '']);
            }
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/select.twig', [
            'id' => $this->getInputId(),
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
        return Craft::t('app', 'Dropdown Options');
    }
}
