<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Elements\Adapters;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\Elements\BaseElementAdapter;
use CraftCms\Cms\User\Contracts\CraftUser;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
class AddressAdapter extends BaseElementAdapter
{
    public static function handle(): string
    {
        return 'addresses';
    }

    public static function elementType(): string
    {
        return Address::class;
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
            'properties' => $this->propertySchemas([
                'ownerId',
                'title',
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
                'firstName',
                'lastName',
                'fullName',
                'latitude',
                'longitude',
            ], [
                'title' => 'Used as the address label, such as Home.',
                'firstName' => 'This is the recipient’s name.',
                'lastName' => 'This is the recipient’s name.',
                'fullName' => 'This is the recipient’s name.',
            ]),
            'additionalProperties' => false,
        ];
    }
}
