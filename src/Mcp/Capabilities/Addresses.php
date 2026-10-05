<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Mcp\CustomFieldSchema;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Addresses
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            ...ElementQueryCriteria::SchemaProperties,
            'ownerId' => [
                ...ElementQueryCriteria::IntegerOrIntegersSchema,
                'description' => 'Owner element ID or IDs.',
            ],
            'countryCode' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Country code criteria.'],
            'administrativeArea' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Administrative area criteria.'],
            'locality' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Locality criteria.'],
            'dependentLocality' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Dependent locality criteria.'],
            'postalCode' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Postal code criteria.'],
            'sortingCode' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Sorting code criteria.'],
            'organization' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Organization criteria.'],
            'organizationTaxId' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Organization tax ID criteria.'],
            'addressLine1' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'First address line criteria.'],
            'addressLine2' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Second address line criteria.'],
            'addressLine3' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Third address line criteria.'],
            'firstName' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'First name criteria.'],
            'lastName' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Last name criteria.'],
            'fullName' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Full name criteria.'],
        ],
        'additionalProperties' => true,
    ];

    private const array AttributesSchema = [
        'type' => 'object',
        'properties' => [
            'ownerId' => ['type' => 'integer', 'description' => 'ID of the element that owns the address.'],
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

    private const array FieldsSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Use addresses.field-schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private McpActor $actor,
        private CustomFieldSchema $customFieldSchema,
        private Elements $elements,
        private ElementQueryCriteria $elementQueryCriteria,
        private UserInitiatedElementSave $userInitiatedElementSave,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Native Craft AddressQuery criteria. Custom field criteria may be passed by field handle.
     * @return array{count: int, limit: int, offset: int, addresses: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'addresses.list',
        description: 'Lists Craft CMS addresses.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
    ): array {
        $actor = $this->actor->user();
        $query = Address::find()->orderBy('elements.id');
        $criteria = $this->elementQueryCriteria->apply($query, $criteria);

        $addresses = collect($query->all())
            ->filter(static fn (Address $address): bool => Gate::forUser($actor)->allows('view', $address))
            ->values();

        return [
            'count' => $addresses->count(),
            'limit' => $criteria['limit'],
            'offset' => $criteria['offset'],
            'addresses' => $addresses->map($this->serialize(...))->all(),
        ];
    }

    /**
     * @param  int|null  $id  Address ID.
     * @param  string|null  $uid  Address UID.
     * @param  int|null  $siteId  Site ID to load the address in.
     * @return array{address: array<string, mixed>}
     */
    #[McpTool(
        name: 'addresses.get',
        description: 'Gets a Craft CMS address by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $address = $this->find($id, $uid, $siteId);

        if (! $address || ! Gate::forUser($this->actor->user())->allows('view', $address)) {
            throw new ToolCallException('Address not found.');
        }

        return ['address' => $this->serialize($address)];
    }

    /**
     * Returns the writable custom-field schema for an existing address or a new address owned by the requested element.
     *
     * @return array{schema: array<string, mixed>}
     */
    #[McpTool(
        name: 'addresses.field-schema',
        description: 'Gets the writable custom-field JSON Schema for an existing address or an address owner.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function fieldSchema(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?int $ownerId = null,
    ): array {
        $existingAddress = $id !== null || $uid !== null;

        if (! $existingAddress && $ownerId === null) {
            throw new ToolCallException('Provide an address ID or UID, or provide ownerId for a new address.');
        }

        $address = $existingAddress
            ? $this->find($id, $uid, $siteId)
            : new Address(['ownerId' => $ownerId]);

        if (! $address) {
            throw new ToolCallException('Address not found.');
        }

        $actor = $this->actor->user();

        if ($existingAddress) {
            $this->authorizeSave($actor, $address);

            if ($ownerId !== null) {
                $address->ownerId = $ownerId;
            }
        }

        $this->authorizeSave($actor, $address);

        return ['schema' => $this->customFieldSchema->forElement($address)];
    }

    /**
     * @param  array<string, mixed>  $attributes  Built-in address attributes.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{address: array<string, mixed>}
     */
    #[McpTool(name: 'addresses.create', description: 'Creates a Craft CMS address. Use addresses.field-schema to discover custom fields.')]
    public function create(
        #[Schema(definition: self::AttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $actor = $this->actor->user();
        $address = new Address;

        $this->populate($address, $attributes, $fields);
        $this->authorizeSave($actor, $address);

        return ['address' => $this->save($address, $actor)];
    }

    /**
     * @param  int|null  $id  Address ID.
     * @param  string|null  $uid  Address UID.
     * @param  int|null  $siteId  Site ID to load the address in.
     * @param  array<string, mixed>  $attributes  Built-in address attributes to update.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{address: array<string, mixed>}
     */
    #[McpTool(
        name: 'addresses.update',
        description: 'Updates a Craft CMS address. Use addresses.field-schema to discover custom fields.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function update(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::AttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $address = $this->find($id, $uid, $siteId);

        if (! $address) {
            throw new ToolCallException('Address not found.');
        }

        $actor = $this->actor->user();

        $this->authorizeSave($actor, $address);
        $this->populate($address, $attributes, $fields);
        $this->authorizeSave($actor, $address);

        return ['address' => $this->save($address, $actor)];
    }

    /**
     * @param  int|null  $id  Address ID.
     * @param  string|null  $uid  Address UID.
     * @param  int|null  $siteId  Site ID to load the address in.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'addresses.delete',
        description: 'Deletes a Craft CMS address.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): array {
        $address = $this->find($id, $uid, $siteId);

        if (! $address || ! Gate::forUser($this->actor->user())->allows('delete', $address)) {
            throw new ToolCallException('Address not found.');
        }

        if (! $this->elements->deleteElement($address, $hardDelete)) {
            throw new ToolCallException('Address could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{address: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://addresses/{id}/sites/{siteId}',
        name: 'craft-addresses-get',
        title: 'Craft Address',
        description: 'A JSON Craft CMS address record addressed by element ID and site ID.',
        mimeType: 'application/json',
    )]
    public function resourceByIdAndSite(int $id, int $siteId): array
    {
        $address = $this->find(id: $id, siteId: $siteId);

        if (! $address || ! Gate::forUser($this->actor->user())->allows('view', $address)) {
            throw new ResourceReadException('Address not found.');
        }

        return ['address' => $this->serialize($address)];
    }

    private function find(?int $id = null, ?string $uid = null, ?int $siteId = null): ?Address
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = Address::find();

        Typecast::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        return $query->one();
    }

    private function authorizeSave(CraftUser $actor, Address $address): void
    {
        if (! Gate::forUser($actor)->allows('save', $address)) {
            throw new ToolCallException('You are not authorized to save this address.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     */
    private function populate(Address $address, array $attributes, array $fields): void
    {
        Typecast::configure($address, $attributes);
        $address->setFieldValues($fields);
    }

    /** @return array<string, mixed> */
    private function save(Address $address, CraftUser $actor): array
    {
        $result = $this->userInitiatedElementSave->save($address, $actor);

        if (! $result->successful || ! $result->element instanceof Address) {
            throw new ToolCallException(implode("\n", $result->element->errors()->all()) ?: 'Address could not be saved.');
        }

        return $this->serialize($result->element);
    }

    /** @return array<string, mixed> */
    private function serialize(Address $address): array
    {
        return Arr::whereNotNull([
            'type' => $address::class,
            ...$address->toArray(),
        ]);
    }
}
