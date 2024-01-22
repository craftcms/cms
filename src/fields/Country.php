<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\fields\conditions\CountryFieldConditionRule;
use craft\helpers\Cp;

/**
 * Email represents an Email field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
class Country extends Field implements PreviewableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Country');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        return !in_array($value, ['', '__BLANK__']) ? $value : null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $options = Craft::$app->getAddresses()->getCountryRepository()->getList(Craft::$app->language);
        array_unshift($options, ['label' => '', 'value' => '__BLANK__']);

        return Cp::selectizeHtml([
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'options' => $options,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return CountryFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        $list = Craft::$app->getAddresses()->getCountryRepository()->getList(Craft::$app->language);
        return $list[$value] ?? $value;
    }
}
