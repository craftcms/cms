<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use Craft;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Database\Table as DbTable;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Enums\ElementIndexViewMode;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Exceptions\InvalidFieldException;
use CraftCms\Cms\Gql\Arguments\Elements\Address as AddressArguments;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Interfaces\Elements\Address as AddressGqlInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\Address as AddressResolver;
use CraftCms\Cms\Gql\Types\Input\Addresses as AddressesInput;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Override;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Addresses field type.
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 */
class Addresses extends Field implements EagerLoadingFieldInterface, ElementContainerFieldInterface, MergeableFieldInterface
{
    public const string VIEW_MODE_CARDS = 'cards';

    public const string VIEW_MODE_INDEX = 'index';

    /**
     * @var int|null The total entries to display per page within element indexes
     */
    public ?int $pageSize = null;

    #[Override]
    public static function displayName(): string
    {
        return t('Addresses');
    }

    #[Override]
    public static function icon(): string
    {
        return 'map-location';
    }

    #[Override]
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            TranslationMethod::Site,
        ];
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', AddressQuery::class, ElementCollection::class, Address::class);
    }

    #[Override]
    public static function dbType(): array|string|null
    {
        return null;
    }

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        /** @var self $field */
        $field = reset($instances);
        $ns = $field->handle.'_'.Str::random(5);

        $exists = DB::table(DbTable::ADDRESSES, "addresses_$ns")
            ->join(new Alias(DbTable::ELEMENTS, "elements_$ns"), "elements_$ns.id", '=', "addresses_$ns.id")
            ->join(new Alias(DbTable::ELEMENTS_OWNERS, "elements_owners_$ns"), "elements_owners_$ns.elementId", '=', "elements_$ns.id")
            ->where("addresses_$ns.fieldId", $field->id)
            ->where("elements_$ns.enabled", true)
            ->whereNull("elements_$ns.dateDeleted")
            ->whereColumn("elements_owners_$ns.ownerId", 'elements.id');

        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':empty:') {
            return $query->whereNotExists($exists);
        }

        if ($value !== ':notempty:') {
            $ids = $value;
            if (! is_array($ids)) {
                $ids = is_string($ids) ? str($ids)->explode(',')->all() : [$ids];
            }

            $ids = array_map(fn ($id) => $id instanceof Address ? $id->id : (int) $id, $ids);

            $exists->whereIn("addresses_$ns.id", $ids);
        }

        return $query->whereExists($exists);
    }

    /**
     * @var int|null Min addresses
     */
    public ?int $minAddresses = null;

    /**
     * @var int|null Max addresses
     */
    public ?int $maxAddresses = null;

    /**
     * @var string The view mode
     *
     * @phpstan-var self::VIEW_MODE_*
     */
    public string $viewMode = self::VIEW_MODE_CARDS;

    /**
     * @see addressManager()
     */
    private NestedElementManager $_addressManager;

    public function __construct($config = [])
    {
        parent::__construct($config);

        if ($this->minAddresses === 0) {
            $this->minAddresses = null;
        }
        if ($this->maxAddresses === 0) {
            $this->maxAddresses = null;
        }
    }

    #[Override]
    public function getRules(): array
    {
        $rules = parent::getRules();

        $rules['minAddresses'] = ['nullable', 'integer', 'min:0'];
        $rules['maxAddresses'] = ['nullable', 'integer', 'min:0'];
        $rules['viewMode'] = [Rule::in([self::VIEW_MODE_CARDS, self::VIEW_MODE_INDEX])];

        return $rules;
    }

    private function addressManager(): NestedElementManager
    {
        $this->_addressManager ??= new NestedElementManager(
            Address::class,
            fn (ElementInterface $owner) => $this->createAddressQuery($owner),
            [
                'field' => $this,
                'criteria' => [
                    'fieldId' => $this->id,
                ],
            ],
        );

        return $this->_addressManager;
    }

    public function getFieldLayoutProviders(): array
    {
        return [
            app(\CraftCms\Cms\Address\Addresses::class),
        ];
    }

    public function getUriFormatForElement(NestedElementInterface $element): ?string
    {
        return null;
    }

    public function getRouteForElement(NestedElementInterface $element): mixed
    {
        return null;
    }

    public function getSupportedSitesForElement(NestedElementInterface $element): array
    {
        try {
            $owner = $element->getOwner();
        } catch (InvalidConfigException) {
            $owner = $element->duplicateOf;
        }

        if (! $owner) {
            return [Sites::getPrimarySite()->id];
        }

        return $this->addressManager()->getSupportedSiteIds($owner);
    }

    public function canViewElement(NestedElementInterface $element, User $user): bool
    {
        return $user->can('view', $element->getOwner());
    }

    public function canSaveElement(NestedElementInterface $element, User $user): bool
    {
        if (! $user->can('save', $owner = $element->getOwner())) {
            return false;
        }

        // If this is a new address, make sure we aren't hitting the Max Addresses limit
        if (! $element->id && $element->getIsCanonical() && $this->maxAddressesReached($owner)) {
            return false;
        }

        return true;
    }

    public function canDuplicateElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        if (! $user->can('save', $owner)) {
            return false;
        }

        // Make sure we aren't hitting the Max Addresses limit
        return ! $this->maxAddressesReached($owner);
    }

    public function canDeleteElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        if (! $user->can('save', $element->getOwner())) {
            return false;
        }

        // Make sure we aren't hitting the Min Addresses limit
        return ! $this->minAddressesReached($owner);
    }

    public function canDeleteElementForSite(NestedElementInterface $element, User $user): bool
    {
        return false;
    }

    private function minAddressesReached(ElementInterface $owner): bool
    {
        return
            $this->minAddresses &&
            $this->minAddresses >= $this->totalAddresses($owner);
    }

    private function maxAddressesReached(ElementInterface $owner): bool
    {
        return
            $this->maxAddresses &&
            $this->maxAddresses <= $this->totalAddresses($owner);
    }

    private function totalAddresses(ElementInterface $owner): int
    {
        /** @var AddressQuery|ElementCollection $value */
        $value = $owner->getFieldValue($this->handle);

        if ($value instanceof AddressQuery) {
            return (clone $value)
                ->drafts(null)
                ->status(null)
                ->siteId($owner->siteId)
                ->getCountForPagination();
        }

        return $value->count();
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $bundle = Craft::$app->getView()->registerAssetBundle(CpAsset::class);

        return template('_components/fieldtypes/Addresses/settings', [
            'field' => $this,
            'readOnly' => $readOnly,
            'baseIconsUrl' => "$bundle->baseUrl/images/view-modes",
        ]);
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValueInternal($value, $element, false);
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValueInternal($value, $element, true);
    }

    private function normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): mixed
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        $query = $this->createAddressQuery($element);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setResultOverride([]);
        } elseif ($value === '*') {
            // preload the nested entries so NestedElementManager::saveNestedElements() doesn't resave them all
            $query->drafts(null)->savedDraftsOnly()->status(null)->limit(null);
            $query->setResultOverride($query->all());
        } elseif ($element && is_array($value)) {
            $query->setResultOverride($this->createAddressesFromSerializedData($value, $element, $fromRequest));
        } elseif (request()->isPreview()) {
            $query->withProvisionalDrafts();
        }

        return $query;
    }

    private function createAddressesFromSerializedData(array $value, ElementInterface $element, bool $fromRequest): array
    {
        // Get the old addresses
        if ($element->id) {
            /** @var Address[] $oldAddressesById */
            $oldAddressesById = Address::find()
                ->fieldId($this->id)
                ->owner($element)
                ->drafts(null)
                ->revisions(null)
                ->status(null)
                ->indexBy('id')
                ->all();
        } else {
            $oldAddressesById = [];
        }

        $addresses = [];
        $prevAddress = null;

        $fieldNamespace = $element->getFieldParamNamespace();
        $baseAddressFieldNamespace = $fieldNamespace ? "$fieldNamespace.$this->handle" : null;

        $nativeFields = [
            'title',
            'fullName',
            'firstName',
            'lastName',
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
            'latitude',
            'longitude',
        ];

        foreach ($value as $addressId => $addressData) {
            // Existing address?
            if (isset($oldAddressesById[$addressId])) {
                /** @var Address $address */
                $address = $oldAddressesById[$addressId];

                // Is this a derivative element, and does the entry primarily belong to the canonical?
                if ($element->getIsDerivative() && $address->getPrimaryOwnerId() === $element->getCanonicalId()) {
                    // Duplicate it as a draft. (We'll drop its draft status from NestedElementManager::saveNestedElements().)
                    $address = app(Drafts::class)->createDraft($address, Auth::user()->id, null, null, [
                        'canonicalId' => $address->id,
                        'primaryOwnerId' => $element->id,
                        'owner' => $element,
                        'siteId' => $element->siteId,
                        'propagating' => false,
                        'markAsSaved' => false,
                    ]);
                }

                $address->forceSave = true;
            } else {
                $address = new Address;
                $address->fieldId = $this->id;
                $address->setPrimaryOwner($element);
                $address->setOwner($element);
                $address->siteId = $element->siteId;
            }

            if (isset($addressData['enabled'])) {
                $address->enabled = (bool) $addressData['enabled'];
            }

            foreach ($nativeFields as $field) {
                if (isset($addressData[$field])) {
                    $address->$field = $addressData[$field];
                }
            }

            $address->setOwner($element);

            // Set the content post location on the entry if we can
            if ($baseAddressFieldNamespace) {
                $address->setFieldParamNamespace("$baseAddressFieldNamespace.$addressId.fields");
            }

            if (isset($addressData['fields'])) {
                foreach ($addressData['fields'] as $fieldHandle => $fieldValue) {
                    try {
                        if ($fromRequest) {
                            $address->setFieldValueFromRequest($fieldHandle, $fieldValue);
                        } else {
                            $address->setFieldValue($fieldHandle, $fieldValue);
                        }
                    } catch (InvalidFieldException) {
                    }
                }
            }

            // Set the prev/next entries
            if ($prevAddress) {
                /** @var ElementInterface $prevAddress */
                $prevAddress->setNext($address);
                /** @var ElementInterface $address */
                $address->setPrev($prevAddress);
            }
            $prevAddress = $address;

            $addresses[] = $address;
        }

        /** @var Address[] $addresses */
        return $addresses;
    }

    private function createAddressQuery(?ElementInterface $owner = null): AddressQuery
    {
        $query = Address::find();

        // Existing element?
        if ($owner && $owner->id) {
            $query->beforeQuery(function (AddressQuery $query) use ($owner) {
                $query->owner($owner);

                // Clear out id=false if this query was populated previously
                if ($query->id === false) {
                    $query->id = null;
                }

                // If the owner is a revision, allow revision addresses to be returned as well
                if ($owner->getIsRevision()) {
                    $query
                        ->revisions(null)
                        ->trashed(null);
                }
            });

            // Prepare the query for lazy eager loading
            $query->prepForEagerLoading($this->handle, $owner);
        } else {
            $query->id = false;
        }

        $query
            ->fieldId($this->id)
            ->siteId($owner->siteId ?? null);

        return $query;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        /** @var AddressQuery|ElementCollection $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $address) {
            /** @var Address $address */
            $addressId = $address->id ?? 'new'.++$new;
            $serialized[$addressId] = [
                'title' => $address->title,
                'countryCode' => $address->countryCode,
                'administrativeArea' => $address->administrativeArea,
                'locality' => $address->locality,
                'dependentLocality' => $address->dependentLocality,
                'postalCode' => $address->postalCode,
                'sortingCode' => $address->sortingCode,
                'addressLine1' => $address->addressLine1,
                'addressLine2' => $address->addressLine2,
                'addressLine3' => $address->addressLine3,
                'organization' => $address->organization,
                'organizationTaxId' => $address->organizationTaxId,
                'fullName' => $address->fullName,
                'enabled' => $address->enabled,
                'fields' => $address->getSerializedFieldValues(),
            ];
        }

        return $serialized;
    }

    #[Override]
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        // We'll do it later from afterElementPropagate()
    }

    public function getElementConditionRuleType(): string
    {
        return EmptyFieldConditionRule::class;
    }

    #[Override]
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->addressManager()->getIsTranslatable($element);
    }

    #[Override]
    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        return $this->addressManager()->getTranslationDescription($element);
    }

    /**
     * @throws InvalidConfigException
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($element);
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->inputHtmlInternal($element, true);
    }

    private function inputHtmlInternal(?ElementInterface $owner, bool $static = false): string
    {
        $config = [
            'showInGrid' => true,
        ];

        if (! $static) {
            $config += [
                'sortable' => true,
                'canCreate' => true,
                'canPaste' => true,
                'minElements' => $this->minAddresses,
                'maxElements' => $this->maxAddresses,
            ];
        }

        if ($this->viewMode === self::VIEW_MODE_CARDS) {
            return $this->addressManager()->getCardsHtml($owner, $config);
        }

        $config += [
            'allowedViewModes' => [ElementIndexViewMode::Cards],
            'pageSize' => $this->pageSize ?? 50,
            // addresses don't have drafts, but in this particular context we need to allow drafts,
            // so that addresses show while adding them via slideout in the element index view mode
            'canHaveDrafts' => true,
        ];

        return $this->addressManager()->getIndexHtml($owner, $config);
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        if (! $element->inScenarios(Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE)) {
            return [];
        }

        return [
            fn ($attribute, AddressQuery|ElementCollection $value, $fail) => $this->validateAddresses($element, $value, $fail),
        ];
    }

    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var AddressQuery|ElementCollection $value */
        return $value->count() === 0;
    }

    private function validateAddresses(ElementInterface $element, AddressQuery|ElementCollection $value, Closure $fail): void
    {
        if ($value instanceof AddressQuery) {
            $addresses = $value->getResultOverride() ?? (clone $value)
                ->drafts(null)
                ->savedDraftsOnly()
                ->status(null)
                ->limit(null)
                ->all();

            $invalidAddressIds = [];
            $scenario = $element->getScenario();

            foreach ($addresses as $address) {
                /** @var Address $address */
                if (
                    $scenario === Element::SCENARIO_ESSENTIALS ||
                    ($address->enabled && $scenario === Element::SCENARIO_LIVE)
                ) {
                    $address->setScenario($scenario);
                }

                if (! $address->validate()) {
                    $invalidAddressIds[] = $address->id;
                }
            }

            if (! empty($invalidAddressIds)) {
                // Just in case the addresses weren't already cached
                $value->setResultOverride($addresses);
                $element->addInvalidNestedElementIds($invalidAddressIds);

                // show a top level error to let users know that there are validation errors in the nested entries
                $fail(t('Validation errors found in {count, plural, =1{one address} other{{count, spellout} addresses}} within the *{fieldName}* field; please fix them.', [
                    'count' => count($invalidAddressIds),
                    'fieldName' => $this->getUiLabel(),
                ]));
            }
        } else {
            $addresses = $value->all();
        }

        if (
            $element->inScenarios(Element::SCENARIO_LIVE) &&
            ($this->minAddresses || $this->maxAddresses)
        ) {
            $rules = [
                $this->handle => array_filter([
                    $this->minAddresses ? "min:{$this->minAddresses}" : null,
                    $this->maxAddresses ? "max:{$this->maxAddresses}" : null,
                ]),
            ];

            $messages = array_filter([
                $this->handle.'.min' => $this->minAddresses ? t('{attribute} should contain at least {min, number} {min, plural, one{address} other{addresses}}.', [
                    'attribute' => t($this->name, category: 'site'),
                    'min' => $this->minAddresses,
                ]) : null,
                $this->handle.'.max' => $this->maxAddresses ? t('{attribute} should contain at most {max, number} {max, plural, one{address} other{addresses}}.', [
                    'attribute' => t($this->name, category: 'site'),
                    'max' => $this->maxAddresses, // Need to pass this in now
                ]) : null,
            ]);

            $validator = Validator::make([$this->handle => $addresses], $rules, $messages);

            if ($validator->fails()) {
                $fail($validator->errors()->first());
            }
        }
    }

    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return $this->addressManager()->getSearchKeywords($element);
    }

    /**
     * @return EagerLoadingMap
     */
    public function getEagerLoadingMap(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = DB::table(DbTable::ADDRESSES, 'addresses')
            ->select([
                'elements_owners.ownerId as source',
                'addresses.id as target',
            ])
            ->join(new Alias(DbTable::ELEMENTS_OWNERS, 'elements_owners'), function (JoinClause $join) use ($sourceElementIds) {
                $join->where('elements_owners.elementId', 'addresses.id')
                    ->whereIn('elements_owners.ownerId', $sourceElementIds);
            })
            ->where('addresses.fieldId', $this->id)
            ->orderBy('elements_owners.sortOrder')
            ->get()
            ->map(fn (object $row) => (array) $row)
            ->all();

        return [
            'elementType' => Address::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
                'allowOwnerDrafts' => true,
                'allowOwnerRevisions' => true,
            ],
        ];
    }

    #[Override]
    public function afterMergeFrom(FieldInterface $outgoingField): void
    {
        DB::table(DbTable::ADDRESSES)
            ->where('fieldId', $outgoingField->id)
            ->update(['fieldId' => $this->id]);

        parent::afterMergeFrom($outgoingField);
    }

    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(AddressGqlInterface::getType()),
            'args' => AddressArguments::getArguments(),
            'resolve' => AddressResolver::class.'::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    #[Override]
    public function getEagerLoadingGqlConditions(): array
    {
        return [
            'withProvisionalDrafts' => request()->isPreview(),
        ];
    }

    #[Override]
    public function getContentGqlMutationArgumentType(): Type
    {
        return Type::listOf(AddressesInput::getType());
    }

    #[Override]
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $this->addressManager()->maintainNestedElements($element, $isNew);
        parent::afterElementPropagate($element, $isNew);
    }

    #[Override]
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (! parent::beforeElementDelete($element)) {
            return false;
        }

        // Delete any addresses that primarily belong to this element
        $this->addressManager()->deleteNestedElements($element, $element->hardDelete);

        return true;
    }

    #[Override]
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any addresses for this element
        $this->addressManager()->restoreNestedElements($element);

        parent::afterElementRestore($element);
    }
}
