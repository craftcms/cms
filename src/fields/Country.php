<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CommerceGuys\Addressing\Country\Country as CountryModel;
use CommerceGuys\Addressing\Exception\UnknownCountryException;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\fields\conditions\CountryFieldConditionRule;
use craft\helpers\Cp;

/**
 * Country represents a Country field.
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
        if ($value instanceof CountryModel) {
            return $value;
        }

        if (!$value || strtolower($value) === '__blank__') {
            return null;
        }

        try {
            return Craft::$app->getAddresses()->getCountryRepository()->get($value, Craft::$app->language);
        } catch (UnknownCountryException) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $options = Craft::$app->getAddresses()->getCountryList(Craft::$app->language);
        array_unshift($options, ['label' => ' ', 'value' => '__blank__']);

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
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        /** @var CountryModel|null $value */
        return $value?->getCountryCode();
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
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        /** @var CountryModel|null $value */
        return $value?->getName() ?? '';
    }
}
