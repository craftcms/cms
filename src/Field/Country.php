<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CommerceGuys\Addressing\Country\Country as CountryModel;
use CommerceGuys\Addressing\Exception\UnknownCountryException;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Conditions\CountryFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Support\Query;
use Override;

use function CraftCms\Cms\t;

/**
 * Country represents a Country field.
 */
class Country extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Country');
    }

    #[Override]
    public static function icon(): string
    {
        return 'flag';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'string|null';
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_STRING;
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof CountryModel) {
            return $value;
        }

        if (! $value || strtolower((string) $value) === '__blank__') {
            return null;
        }

        try {
            return app(Addresses::class)->getCountryRepository()->get($value, app()->getLocale());
        } catch (UnknownCountryException) {
            return null;
        }
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $options = collect(app(Addresses::class)->getCountryList(app()->getLocale()))
            ->map(fn (string $label, string $value): array => compact('label', 'value'))
            ->values()
            ->all();

        return Choice::make($context->path)
            ->options($options)
            ->value($this->serializeValue($context->value, $context->element));
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $options = app(Addresses::class)->getCountryList(app()->getLocale());
        array_unshift($options, ['label' => ' ', 'value' => '__blank__']);

        return FormFields::selectizeHtml([
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'options' => $options,
            'value' => $value,
        ]);
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        /** @var CountryModel|null $value */
        return $value?->getCountryCode();
    }

    public function getElementConditionRuleType(): string
    {
        return CountryFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var CountryModel|null $value */
        return $value?->getName() ?? '';
    }

    #[Override]
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
