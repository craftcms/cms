<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Validation;

use craft\fieldlayoutelements\addresses\LatLongField;
use craft\fieldlayoutelements\addresses\OrganizationField;
use craft\fieldlayoutelements\addresses\OrganizationTaxIdField;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\FullNameField;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Validation\Rules\DisallowMb4;
use Illuminate\Validation\Rule;
use Override;

/**
 * @extends ElementRules<Address>
 *
 * @property Address $component
 */
final class AddressRules extends ElementRules
{
    /**
     * @var list<class-string<BaseNativeField>>
     */
    private const array REQUIRABLE_NATIVE_FIELDS = [
        OrganizationField::class,
        OrganizationTaxIdField::class,
        FullNameField::class,
        LatLongField::class,
    ];

    private const array TRIMMABLE_ATTRIBUTES = [
        'countryCode',
        'administrativeArea',
        'locality',
        'dependentLocality',
        'postalCode',
        'sortingCode',
        'addressLine1',
        'addressLine2',
        'addressLine3',
        'organization',
        'organizationTaxId',
        'fullName',
        'firstName',
        'lastName',
        'latitude',
        'longitude',
    ];

    #[Override]
    public function prepareForValidation(?array $attributeNames = null): void
    {
        parent::prepareForValidation($attributeNames);

        $attributesToTrim = is_null($attributeNames)
            ? self::TRIMMABLE_ATTRIBUTES
            : array_intersect(self::TRIMMABLE_ATTRIBUTES, $attributeNames);

        foreach ($attributesToTrim as $attribute) {
            $value = $this->component->{$attribute};

            if (is_string($value)) {
                $this->component->{$attribute} = trim($value);
            }
        }
    }

    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules = $this->addAddressAttributeRules($rules);
        $rules = $this->addCountryCodeValidation($rules);
        $rules = $this->addAddressFormatRequirements($rules);
        $rules = $this->addFieldLayoutRequirements($rules);

        return $this->addCoordinateValidation($rules);
    }

    private function addAddressAttributeRules(array $rules): array
    {
        $rules['fieldId'] = ['nullable', 'integer'];
        $rules['ownerId'] = ['nullable', 'integer'];
        $rules['primaryOwnerId'] = ['nullable', 'integer'];
        $rules['countryCode'] = ['required', 'string', 'max:255', new DisallowMb4];
        $rules['administrativeArea'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['locality'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['dependentLocality'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['postalCode'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['sortingCode'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['addressLine1'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['addressLine2'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['addressLine3'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['organization'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['organizationTaxId'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['fullName'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['firstName'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['lastName'] = ['nullable', 'string', 'max:255', new DisallowMb4];
        $rules['latitude'] = ['nullable', new DisallowMb4];
        $rules['longitude'] = ['nullable', new DisallowMb4];

        return $rules;
    }

    private function addCountryCodeValidation(array $rules): array
    {
        $countryCodes = array_keys(app(Addresses::class)->getCountryRepository()->getList());
        $rules['countryCode'][] = Rule::in($countryCodes);

        return $rules;
    }

    private function addAddressFormatRequirements(array $rules): array
    {
        foreach (Address::addressAttributes() as $attribute) {
            if ($attribute === 'countryCode') {
                continue;
            }

            $rules[$attribute] ??= [];
            $rules[$attribute][] = Rule::requiredIf(fn () => $this->isRequiredByAddressFormat($attribute));
        }

        return $rules;
    }

    private function isRequiredByAddressFormat(string $attribute): bool
    {
        if (! $this->component->inScenarios(Element::SCENARIO_LIVE)) {
            return false;
        }

        $formatter = app(Addresses::class)
            ->getAddressFormatRepository()
            ->get($this->component->countryCode);

        return in_array($attribute, $formatter->getRequiredFields());
    }

    private function addFieldLayoutRequirements(array $rules): array
    {
        $fieldLayout = $this->component->getFieldLayout();

        foreach (self::REQUIRABLE_NATIVE_FIELDS as $fieldClass) {
            /** @var BaseNativeField|null $field */
            $field = $fieldLayout->getFirstVisibleElementByType($fieldClass, $this->component);

            if (! $field?->required) {
                continue;
            }

            foreach ($this->resolveFieldAttributes($field) as $attribute) {
                $rules[$attribute] ??= [];
                $rules[$attribute][] = Rule::requiredIf($this->component->inScenarios(Element::SCENARIO_LIVE));
            }
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    private function resolveFieldAttributes(BaseNativeField $field): array
    {
        $attributes = Arr::wrap($field->attribute());

        return match ($attributes) {
            ['latLong'] => ['latitude', 'longitude'],
            ['fullName'] => Cms::config()->showFirstAndLastNameFields
                ? ['firstName', 'lastName']
                : $attributes,
            default => $attributes,
        };
    }

    private function addCoordinateValidation(array $rules): array
    {
        $coordinateScenarios = $this->component->inScenarios(Element::SCENARIO_LIVE, Element::SCENARIO_DEFAULT);

        $rules['latitude'][] = Rule::when($coordinateScenarios, [
            'numeric',
            'min:-90',
            'max:90',
        ]);

        $rules['longitude'][] = Rule::when($coordinateScenarios, [
            'numeric',
            'min:-180',
            'max:180',
        ]);

        return $rules;
    }
}
