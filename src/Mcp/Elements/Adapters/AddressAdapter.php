<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Elements\Adapters;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\Elements\BaseElementAdapter;
use CraftCms\Cms\User\Contracts\CraftUser;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
class AddressAdapter extends BaseElementAdapter
{
    private const array StringCriteria = [
        'countryCode' => 'Country code criteria.',
        'administrativeArea' => 'Administrative area criteria.',
        'locality' => 'Locality criteria.',
        'dependentLocality' => 'Dependent locality criteria.',
        'postalCode' => 'Postal code criteria.',
        'sortingCode' => 'Sorting code criteria.',
        'organization' => 'Organization criteria.',
        'organizationTaxId' => 'Organization tax ID criteria.',
        'addressLine1' => 'First address line criteria.',
        'addressLine2' => 'Second address line criteria.',
        'addressLine3' => 'Third address line criteria.',
        'firstName' => 'First name criteria.',
        'lastName' => 'Last name criteria.',
        'fullName' => 'Full name criteria.',
    ];

    public static function handle(): string
    {
        return 'addresses';
    }

    public static function elementType(): string
    {
        return Address::class;
    }

    protected function criteriaProperties(): array
    {
        $properties = [
            'ownerId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Owner element ID or IDs.'],
        ];

        foreach (self::StringCriteria as $name => $description) {
            $properties[$name] = [...ElementQueryCriteria::StringOrStringsSchema, 'description' => $description];
        }

        return $properties;
    }

    protected function createAttributesSchema(): array
    {
        return $this->attributesSchema();
    }

    protected function updateAttributesSchema(): array
    {
        return $this->attributesSchema();
    }

    protected function fieldSchemaContextSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'For a new address, provide ownerId. For an existing address, ownerId previews the layout for another owner.',
            'properties' => [
                'ownerId' => ['type' => 'integer', 'description' => 'ID of the element that owns the address.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function duplicateModes(): array
    {
        return ['copy'];
    }

    protected function notes(): array
    {
        return ['Duplicating an address keeps its owner.'];
    }

    /** @param Address|null $element */
    public function fieldSchemaElement(?ElementInterface $element, array $context, CraftUser $actor): ElementInterface
    {
        $ownerId = $context['ownerId'] ?? null;

        if ($element === null && $ownerId === null) {
            throw new ToolCallException('Provide an address ID or UID, or provide context.ownerId for a new address.');
        }

        if ($element !== null) {
            $this->authorizeSave($actor, $element);

            if ($ownerId !== null) {
                $element->ownerId = $ownerId;
            }
        }

        return parent::fieldSchemaElement($element, $context, $actor);
    }

    public function duplicate(ElementInterface $element, ?string $mode): ElementInterface
    {
        if ($mode !== null && $mode !== 'copy') {
            throw new ToolCallException('Addresses only support the copy duplication mode.');
        }

        return $this->lifecycle->duplicate($element);
    }

    /** @return array<string, mixed> */
    private function attributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ownerId' => ['type' => 'integer', 'description' => 'ID of the element that owns the address.'],
                'title' => ['type' => ['string', 'null'], 'description' => 'Address label, such as Home.'],
                'countryCode' => ['type' => 'string', 'description' => 'ISO 3166-1 alpha-2 country code.'],
                'administrativeArea' => ['type' => ['string', 'null'], 'description' => 'State, province, region, or administrative area.'],
                'locality' => ['type' => ['string', 'null'], 'description' => 'City, town, or locality.'],
                'dependentLocality' => ['type' => ['string', 'null'], 'description' => 'District, neighborhood, or suburb.'],
                'postalCode' => ['type' => ['string', 'null'], 'description' => 'Postal or ZIP code.'],
                'sortingCode' => ['type' => ['string', 'null'], 'description' => 'Sorting code.'],
                'addressLine1' => ['type' => ['string', 'null'], 'description' => 'First street address line.'],
                'addressLine2' => ['type' => ['string', 'null'], 'description' => 'Second street address line.'],
                'addressLine3' => ['type' => ['string', 'null'], 'description' => 'Third street address line.'],
                'organization' => ['type' => ['string', 'null'], 'description' => 'Organization or company name.'],
                'organizationTaxId' => ['type' => ['string', 'null'], 'description' => 'Organization tax or VAT ID.'],
                'firstName' => ['type' => ['string', 'null'], 'description' => 'Recipient first name.'],
                'lastName' => ['type' => ['string', 'null'], 'description' => 'Recipient last name.'],
                'fullName' => ['type' => ['string', 'null'], 'description' => 'Recipient full name.'],
                'latitude' => ['type' => ['number', 'string', 'null'], 'description' => 'Latitude coordinate.'],
                'longitude' => ['type' => ['number', 'string', 'null'], 'description' => 'Longitude coordinate.'],
            ],
            'additionalProperties' => false,
        ];
    }
}
