<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\MergeableFieldInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\Query;
use craft\db\Table as DbTable;
use craft\elements\Address;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\elements\NestedElementManager;
use craft\elements\User;
use craft\enums\ElementIndexViewMode;
use craft\errors\InvalidFieldException;
use craft\events\CancelableEvent;
use craft\fields\conditions\EmptyFieldConditionRule;
use craft\gql\arguments\elements\Address as AddressArguments;
use craft\gql\interfaces\elements\Address as AddressGqlInterface;
use craft\gql\resolvers\elements\Address as AddressResolver;
use craft\gql\types\input\Addresses as AddressesInput;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\StringHelper;
use craft\services\Elements;
use craft\validators\ArrayValidator;
use craft\web\assets\cp\CpAsset;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Addresses field type.
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Addresses extends Field implements
    ElementContainerFieldInterface,
    EagerLoadingFieldInterface,
    MergeableFieldInterface
{
    public const VIEW_MODE_CARDS = 'cards';
    public const VIEW_MODE_INDEX = 'index';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Addresses');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'map-location';
    }

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', AddressQuery::class, ElementCollection::class, Address::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): array
    {
        /** @var self $field */
        $field = reset($instances);
        $ns = $field->handle . '_' . StringHelper::randomString(5);

        $existsQuery = (new Query())
            ->from(["addresses_$ns" => DbTable::ADDRESSES])
            ->innerJoin(["elements_$ns" => DbTable::ELEMENTS], "[[elements_$ns.id]] = [[addresses_$ns.id]]")
            ->innerJoin(["elements_owners_$ns" => DbTable::ELEMENTS_OWNERS], "[[elements_owners_$ns.elementId]] = [[elements_$ns.id]]")
            ->andWhere([
                "addresses_$ns.fieldId" => $field->id,
                "elements_$ns.enabled" => true,
                "elements_$ns.dateDeleted" => null,
                "[[elements_owners_$ns.ownerId]]" => new Expression('[[elements.id]]'),
            ]);

        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':empty:') {
            return ['not exists', $existsQuery];
        }

        if ($value !== ':notempty:') {
            $ids = $value;
            if (!is_array($ids)) {
                $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
            }

            $ids = array_map(fn($id) => $id instanceof Address ? $id->id : (int)$id, $ids);

            $existsQuery->andWhere(["addresses_$ns.id" => $ids]);
        }

        return ['exists', $existsQuery];
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
     * @phpstan-var self::VIEW_MODE_*
     */
    public string $viewMode = self::VIEW_MODE_CARDS;

    /**
     * @see addressManager()
     */
    private NestedElementManager $_addressManager;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->minAddresses === 0) {
            $this->minAddresses = null;
        }
        if ($this->maxAddresses === 0) {
            $this->maxAddresses = null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minAddresses', 'maxAddresses'], 'integer', 'min' => 0];
        $rules[] = [['viewMode'], 'in', 'range' => [self::VIEW_MODE_CARDS, self::VIEW_MODE_INDEX]];
        return $rules;
    }

    private function addressManager(): NestedElementManager
    {
        if (!isset($this->_addressManager)) {
            $this->_addressManager = new NestedElementManager(
                Address::class,
                fn(ElementInterface $owner) => $this->createAddressQuery($owner),
                [
                    'field' => $this,
                    'criteria' => [
                        'fieldId' => $this->id,
                    ],
                ],
            );
        }

        return $this->_addressManager;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayoutProviders(): array
    {
        return [
            Craft::$app->getAddresses(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUriFormatForElement(NestedElementInterface $element): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRouteForElement(NestedElementInterface $element): mixed
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSitesForElement(NestedElementInterface $element): array
    {
        try {
            $owner = $element->getOwner();
        } catch (InvalidConfigException) {
            $owner = $element->duplicateOf;
        }

        if (!$owner) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        return $this->addressManager()->getSupportedSiteIds($owner);
    }

    /**
     * @inheritdoc
     */
    public function canViewElement(NestedElementInterface $element, User $user): ?bool
    {
        return Craft::$app->getElements()->canView($element->getOwner(), $user);
    }

    /**
     * @inheritdoc
     */
    public function canSaveElement(NestedElementInterface $element, User $user): ?bool
    {
        if (!Craft::$app->getElements()->canSave($element->getOwner(), $user)) {
            return false;
        }

        // If this is a new address, make sure we aren't hitting the Max Addresses limit
        if (!$element->id && $element->getIsCanonical() && $this->maxAddressesReached($element->getOwner())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDuplicateElement(NestedElementInterface $element, User $user): ?bool
    {
        $owner = $element->getOwner();
        if (!Craft::$app->getElements()->canSave($owner, $user)) {
            return false;
        }

        // Make sure we aren't hitting the Max Addresses limit
        return !$this->maxAddressesReached($owner);
    }

    /**
     * @inheritdoc
     */
    public function canDeleteElement(NestedElementInterface $element, User $user): ?bool
    {
        $owner = $element->getOwner();
        if (!Craft::$app->getElements()->canSave($element->getOwner(), $user)) {
            return false;
        }

        // Make sure we aren't hitting the Min Addresses limit
        return !$this->minAddressesReached($owner);
    }

    /**
     * @inheritdoc
     */
    public function canDeleteElementForSite(NestedElementInterface $element, User $user): ?bool
    {
        return false;
    }

    private function minAddressesReached(ElementInterface $owner): bool
    {
        return (
            $this->minAddresses &&
            $this->minAddresses >= $this->totalAddresses($owner)
        );
    }

    private function maxAddressesReached(ElementInterface $owner): bool
    {
        return (
            $this->maxAddresses &&
            $this->maxAddresses <= $this->totalAddresses($owner)
        );
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
                ->limit(null)
                ->count();
        }

        return $value->count();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $bundle = Craft::$app->getView()->registerAssetBundle(CpAsset::class);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Addresses/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
            'baseIconsUrl' => "$bundle->baseUrl/images/view-modes",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValueInternal($value, $element, false);
    }

    /**
     * @inheritdoc
     */
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
            $query->setCachedResult([]);
        } elseif ($value === '*') {
            // preload the nested entries so NestedElementManager::saveNestedElements() doesn't resave them all
            $query->drafts(null)->savedDraftsOnly()->status(null)->limit(null);
            $query->setCachedResult($query->all());
        } elseif ($element && is_array($value)) {
            $query->setCachedResult($this->createAddressesFromSerializedData($value, $element, $fromRequest));
        } elseif (Craft::$app->getRequest()->getIsPreview()) {
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
                    $address = Craft::$app->getDrafts()->createDraft($address, Craft::$app->getUser()->getId(), null, null, [
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
                $address = new Address();
                $address->fieldId = $this->id;
                $address->setPrimaryOwner($element);
                $address->setOwner($element);
                $address->siteId = $element->siteId;
            }

            if (isset($addressData['enabled'])) {
                $address->enabled = (bool)$addressData['enabled'];
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
            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_BEFORE_PREPARE => function(
                    CancelableEvent $event,
                    AddressQuery $query,
                ) use ($owner) {
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
                },
            ], true));

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

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        /** @var AddressQuery|ElementCollection $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $address) {
            /** @var Address $address */
            $addressId = $address->id ?? 'new' . ++$new;
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

    /**
     * @inheritdoc
     */
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        // We'll do it later from afterElementPropagate()
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return EmptyFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->addressManager()->getIsTranslatable($element);
    }

    /**
     * @inheritdoc
     */
    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        return $this->addressManager()->getTranslationDescription($element);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->inputHtmlInternal($element, true);
    }

    private function inputHtmlInternal(?ElementInterface $owner, bool $static = false): string
    {
        $config = [
            'showInGrid' => true,
        ];

        if (!$static) {
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

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                fn(ElementInterface $element) => $this->validateAddresses($element),
                'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
                'skipOnEmpty' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var AddressQuery|ElementCollection $value */
        return $value->count() === 0;
    }

    private function validateAddresses(ElementInterface $element): void
    {
        /** @var AddressQuery|ElementCollection $value */
        $value = $element->getFieldValue($this->handle);

        if ($value instanceof AddressQuery) {
            $addresses = $value->getCachedResult() ?? (clone $value)
                ->drafts(null)
                ->savedDraftsOnly()
                ->status(null)
                ->limit(null)
                ->all();

            $invalidAddressIds = [];
            $scenario = $element->getScenario();

            foreach ($addresses as $i => $address) {
                /** @var Address $address */
                if (
                    $scenario === Element::SCENARIO_ESSENTIALS ||
                    ($address->enabled && $scenario === Element::SCENARIO_LIVE)
                ) {
                    $address->setScenario($scenario);
                }

                if (!$address->validate()) {
                    $invalidAddressIds[] = $address->id;
                }
            }

            if (!empty($invalidAddressIds)) {
                // Just in case the addresses weren't already cached
                $value->setCachedResult($addresses);
                $element->addInvalidNestedElementIds($invalidAddressIds);

                // show a top level error to let users know that there are validation errors in the nested entries
                $element->addError($this->handle, Craft::t('app', 'Validation errors found in {count, plural, =1{one address} other{{count, spellout} addresses}} within the *{fieldName}* field; please fix them.', [
                    'count' => count($invalidAddressIds),
                    'fieldName' => $this->getUiLabel(),
                ]));
            }
        } else {
            $addresses = $value->all();
        }

        if (
            $element->getScenario() === Element::SCENARIO_LIVE &&
            ($this->minAddresses || $this->maxAddresses)
        ) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->minAddresses ?: null,
                'max' => $this->maxAddresses ?: null,
                'tooFew' => $this->minAddresses ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{address} other{addresses}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'min' => $this->minAddresses, // Need to pass this in now
                ]) : null,
                'tooMany' => $this->maxAddresses ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{address} other{addresses}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'max' => $this->maxAddresses, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($addresses, $error)) {
                $element->addError($this->handle, $error);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return $this->addressManager()->getSearchKeywords($element);
    }

    /**
     * @inheritdoc
     * @return EagerLoadingMap|null|false
     */
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select([
                'source' => 'elements_owners.ownerId',
                'target' => 'addresses.id',
            ])
            ->from(['addresses' => DbTable::ADDRESSES])
            ->innerJoin(['elements_owners' => DbTable::ELEMENTS_OWNERS], [
                'and',
                '[[elements_owners.elementId]] = [[addresses.id]]',
                ['elements_owners.ownerId' => $sourceElementIds],
            ])
            ->where(['addresses.fieldId' => $this->id])
            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
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

    /**
     * @inheritdoc
     */
    public function afterMergeFrom(FieldInterface $outgoingField): void
    {
        Db::update(DbTable::ADDRESSES, ['fieldId' => $this->id], ['fieldId' => $outgoingField->id]);
        parent::afterMergeFrom($outgoingField);
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(AddressGqlInterface::getType()),
            'args' => AddressArguments::getArguments(),
            'resolve' => AddressResolver::class . '::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingGqlConditions(): ?array
    {
        return [
            'withProvisionalDrafts' => Craft::$app->getRequest()->getIsPreview(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return Type::listOf(AddressesInput::getType());
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $this->addressManager()->maintainNestedElements($element, $isNew);
        parent::afterElementPropagate($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (!parent::beforeElementDelete($element)) {
            return false;
        }

        // Delete any addresses that primarily belong to this element
        $this->addressManager()->deleteNestedElements($element, $element->hardDelete);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any addresses for this element
        $this->addressManager()->restoreNestedElements($element);

        parent::afterElementRestore($element);
    }
}
