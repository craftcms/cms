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
use CraftCms\Cms\Shared\Rules\DisallowMb4;
use CraftCms\Cms\Shared\Rules\Trim;
use CraftCms\Cms\Support\Arr;
use Illuminate\Validation\Rule;
use Override;

/**
 * @extends ElementRules<Address>
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
        $rules['countryCode'] = ['required', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['administrativeArea'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['locality'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['dependentLocality'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['postalCode'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['sortingCode'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['addressLine1'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['addressLine2'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['addressLine3'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['organization'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['organizationTaxId'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['fullName'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['firstName'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['lastName'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['latitude'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];
        $rules['longitude'] = ['nullable', 'string', 'max:255', new DisallowMb4, new Trim($this->component)];

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
        if (! $this->inScenarios(Element::SCENARIO_LIVE)) {
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
                $rules[$attribute][] = Rule::requiredIf($this->inScenarios(Element::SCENARIO_LIVE));
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
        $coordinateScenarios = $this->inScenarios(Element::SCENARIO_LIVE, Element::SCENARIO_DEFAULT);

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
