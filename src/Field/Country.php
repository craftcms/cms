<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CommerceGuys\Addressing\Country\Country as CountryModel;
use CommerceGuys\Addressing\Exception\UnknownCountryException;
use craft\base\ElementInterface;
use craft\fields\conditions\CountryFieldConditionRule;
use craft\helpers\Cp;
use CraftCms\Cms\Addresses\Addresses;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Country represents a Country field.
 */
final class Country extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return t('Country');
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'flag';
    }

    /**
     * {@inheritdoc}
     */
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * {@inheritdoc}
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof CountryModel) {
            return $value;
        }

        if (! $value || strtolower($value) === '__blank__') {
            return null;
        }

        try {
            return app(Addresses::class)->getCountryRepository()->get($value, app()->getLocale());
        } catch (UnknownCountryException) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $options = app(Addresses::class)->getCountryList(app()->getLocale());
        array_unshift($options, ['label' => ' ', 'value' => '__blank__']);

        return Cp::selectizeHtml([
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'options' => $options,
            'value' => $value,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        /** @var CountryModel|null $value */
        return $value?->getCountryCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return CountryFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var CountryModel|null $value */
        return $value?->getName() ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $countries = app(Addresses::class)->getCountryRepository()->getList(app()->getLocale());
            $value = $countries[array_rand($countries)];
        } else {
            if ($value instanceof CountryModel) {
                $value = $value->getName();
            }
        }

        return $value;
    }
}
