<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Support\Arr;

/**
 * The address fields owned by the Address FieldLayout element. Its canonical
 * value is a map of address field names to strings or null. Country is supplied
 * separately because the FieldLayout owns it through CountryCodeField.
 *
 * @phpstan-type AddressValue array{
 *     addressLine1?: string|null,
 *     addressLine2?: string|null,
 *     addressLine3?: string|null,
 *     administrativeArea?: string|null,
 *     locality?: string|null,
 *     dependentLocality?: string|null,
 *     postalCode?: string|null,
 *     sortingCode?: string|null,
 * }
 */
class Address extends Control
{
    private ?string $countryCode = null;

    private bool $belongsToCurrentUser = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $address = self::address($control->props['countryCode'], $value);
        $render = fn (): string => FormFields::addressFieldsHtml(
            $address,
            $attributes['name'] === null,
            $control->props['belongsToCurrentUser'],
        );

        return $attributes['name'] === null
            ? $render()
            : InputNamespace::namespaceInputs($render, $attributes['name']);
    }

    public function component(): string
    {
        return 'craft:address';
    }

    public function countryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function belongsToCurrentUser(bool $belongsToCurrentUser = true): static
    {
        $this->belongsToCurrentUser = $belongsToCurrentUser;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $countryCode = $this->countryCode ?? Cms::config()->defaultCountryCode;
        $address = self::address($countryCode, $value);
        $fields = app(Addresses::class)->getFormFieldDefinitions($address, $this->belongsToCurrentUser);

        return [
            'countryCode' => $countryCode,
            'belongsToCurrentUser' => $this->belongsToCurrentUser,
            'fields' => array_map(
                fn (array $field): array => Arr::except($field, ['errors', 'value']),
                $fields,
            ),
        ];
    }

    private static function address(string $countryCode, mixed $value): AddressElement
    {
        $value = is_array($value) ? Arr::only($value, [
            'addressLine1',
            'addressLine2',
            'addressLine3',
            'administrativeArea',
            'locality',
            'dependentLocality',
            'postalCode',
            'sortingCode',
        ]) : [];

        return new AddressElement(['countryCode' => $countryCode, ...$value]);
    }
}
