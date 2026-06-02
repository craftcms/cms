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
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\fields\conditions\CountryFieldConditionRule;
use craft\helpers\Cp;
use yii\db\Schema;

/**
 * Country represents a Country field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
class Country extends Field implements InlineEditableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
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
    public static function icon(): string
    {
        return 'flag';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
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
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var CountryModel|null $value */
        return $value?->getName() ?? '';
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $countries = Craft::$app->getAddresses()->getCountryRepository()->getList(Craft::$app->language);
            $value = $countries[array_rand($countries)];
        } else {
            if ($value instanceof CountryModel) {
                $value = $value->getName();
            }
        }

        return $value;
    }
}
