<?php

namespace craft\elements;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Country\Country;
use CommerceGuys\Addressing\Subdivision\SubdivisionUpdater;
use Craft;
use craft\base\Element;
use craft\base\NameTrait;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\db\Table;
use craft\elements\actions\Copy;
use craft\elements\conditions\addresses\AddressCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\AddressQuery;
use craft\fieldlayoutelements\addresses\LatLongField;
use craft\fieldlayoutelements\addresses\OrganizationField;
use craft\fieldlayoutelements\addresses\OrganizationTaxIdField;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\FullNameField;
use craft\models\FieldLayout;
use craft\records\Address as AddressRecord;
use craft\validators\StringValidator;
use yii\base\InvalidConfigException;

/**
 * Address element class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Element implements AddressInterface, NestedElementInterface
{
    use NameTrait;
    use NestedElementTrait;

    /**
     * @since 5.0.0
     */
    public const GQL_TYPE_NAME = 'Address';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Address');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'address');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Addresses');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'addresses');
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(AddressCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        return [
            Copy::class,
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), [
            'country' => ['label' => Craft::t('app', 'Country')],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'country':
                return $this->getCountry()->getName();
            default:
                return parent::attributeHtml($attribute);
        }
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('app', 'Label'),
                'orderBy' => 'title',
                'attribute' => 'title',
            ],
            [
                'label' => Craft::t('app', 'Country'),
                'orderBy' => 'countryCode',
                'attribute' => 'country',
            ],
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     * @return AddressQuery The newly created [[AddressQuery]] instance.
     */
    public static function find(): AddressQuery
    {
        return new AddressQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return self::_addressAttributes();
    }

    /**
     * Returns all attributes
     *
     * @return string[]
     */
    private static function _addressAttributes(): array
    {
        return [
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
        ];
    }

    /**
     * Returns an address attribute label.
     *
     * @param string $attribute
     * @param string $countryCode
     * @return string|null
     * @deprecated in 4.3.0. [[\craft\services\Addresses::getFieldLabel()]] should be used instead.
     */
    public static function addressAttributeLabel(string $attribute, string $countryCode): ?string
    {
        if (!AddressField::exists($attribute)) {
            return null;
        }
        /** @phpstan-var AddressField::* $attribute */
        return Craft::$app->getAddresses()->getFieldLabel($attribute, $countryCode);
    }

    /**
     * @var string Two-letter country code
     * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
     */
    public string $countryCode;

    /**
     * @var string|null Administrative area
     */
    public ?string $administrativeArea = null;

    /**
     * @var string|null Locality
     */
    public ?string $locality = null;

    /**
     * @var string|null Dependent locality
     */
    public ?string $dependentLocality = null;

    /**
     * @var string|null Postal code
     */
    public ?string $postalCode = null;

    /**
     * @var string|null Sorting code
     */
    public ?string $sortingCode = null;

    /**
     * @var string|null First line of the address
     */
    public ?string $addressLine1 = null;

    /**
     * @var string|null Second line of the address
     */
    public ?string $addressLine2 = null;

    /**
     * @var string|null Third line of the address
     * @since 5.0.0
     */
    public ?string $addressLine3 = null;

    /**
     * @var string|null Organization name
     */
    public ?string $organization = null;

    /**
     * @var string|null Organization tax ID
     */
    public ?string $organizationTaxId = null;

    /**
     * @var string|null Latitude
     */
    public ?string $latitude = null;

    /**
     * @var string|null Longitude
     */
    public ?string $longitude = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->countryCode)) {
            $this->countryCode = Craft::$app->getConfig()->getGeneral()->defaultCountryCode;
        }

        $this->normalizeNames();
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        // Don't even allow setting a blank country code
        if (array_key_exists('countryCode', $values) && empty($values['countryCode'])) {
            unset($values['countryCode']);
        }

        if (array_key_exists('firstName', $values) || array_key_exists('lastName', $values)) {
            // Unset fullName so NameTrait::prepareNamesForSave() can set it
            $this->fullName = null;
        } elseif (array_key_exists('fullName', $values)) {
            // Unset firstName and lastName so NameTrait::prepareNamesForSave() can set them
            $this->firstName = $this->lastName = null;
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabel($attribute): string
    {
        if (AddressField::exists($attribute)) {
            /** @phpstan-var AddressField::* $attribute */
            return Craft::$app->getAddresses()->getFieldLabel($attribute, $this->countryCode);
        }

        return match ($attribute) {
            'title' => Craft::t('app', 'Label'),
            'organizationTaxId' => Craft::t('app', 'Organization Tax ID'),
            'fullName' => Craft::t('app', 'Full Name'),
            'firstName' => Craft::t('app', 'First Name'),
            'lastName' => Craft::t('app', 'Last Name'),
            'latitude' => Craft::t('app', 'Latitude'),
            'longitude' => Craft::t('app', 'Longitude'),
            default => parent::getAttributeLabel($attribute),
        };
    }

    /**
     * Returns whether the address belongs to the currently logged-in user.
     *
     * @return bool
     * @since 4.5.13
     */
    public function getBelongsToCurrentUser(): bool
    {
        $owner = $this->getOwner();
        return $owner instanceof User && $owner->getIsCurrent();
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        $owner = $this->getOwner()?->getCanonical(true);
        if (!$owner) {
            return false;
        }

        return Craft::$app->getElements()->canView($owner, $user);
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        $owner = $this->getOwner()?->getCanonical(true);
        if (!$owner) {
            return false;
        }

        return Craft::$app->getElements()->canSave($owner, $user);
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        $owner = $this->getOwner()?->getCanonical(true);
        if (!$owner) {
            return false;
        }

        return Craft::$app->getElements()->canSave($owner, $user);
    }

    /**
     * @inheritdoc
     */
    public function canCopy(User $user): bool
    {
        return Craft::$app->getElements()->canDuplicate($this, $user);
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        $owner = $this->getOwner()?->getCanonical(true);
        if (!$owner) {
            return false;
        }

        return Craft::$app->getElements()->canSave($owner, $user);
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Returns a [[Country]] object representing the address’ country.
     *
     * @return Country
     * @since 5.3.0
     */
    public function getCountry(): Country
    {
        return Craft::$app->getAddresses()->getCountryRepository()->get($this->countryCode, Craft::$app->language);
    }

    /**
     * @inheritdoc
     */
    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    /**
     * @inheritdoc
     */
    public function getLocality(): ?string
    {
        return $this->locality;
    }

    /**
     * @inheritdoc
     */
    public function getDependentLocality(): ?string
    {
        return $this->dependentLocality;
    }

    /**
     * @inheritdoc
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @inheritdoc
     */
    public function getSortingCode(): ?string
    {
        return $this->sortingCode;
    }

    /**
     * @inheritdoc
     */
    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    /**
     * @inheritdoc
     */
    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    /**
     * @inheritdoc
     */
    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    /**
     * @inheritdoc
     */
    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    /**
     * @inheritdoc
     */
    public function getGivenName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalName(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getFamilyName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @inheritdoc
     */
    public function getLocale(): string
    {
        return Craft::$app->language;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return self::GQL_TYPE_NAME;
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        $usedFields = array_unique([
            ...Craft::$app->getAddresses()->getUsedFields($this->countryCode),
            'fullName',
            'latLong',
            'organizationTaxId',
            'organization',
            'countryCode',
        ]);
        $nullFields = array_filter(
            array_diff(self::_addressAttributes(), $usedFields),
            fn(string $attribute) => !in_array($attribute, ['givenName', 'familyName', 'additionalName']),
        );

        foreach ($nullFields as $field) {
            $this->$field = null;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fieldId', 'ownerId', 'primaryOwnerId'], 'number'];
        $rules[] = [['countryCode'], 'required'];

        $stringFields = [
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
        $rules[] = [$stringFields, 'trim', 'skipOnEmpty' => true];
        $rules[] = [$stringFields, StringValidator::class, 'max' => 255, 'disallowMb4' => true];

        $addressesService = Craft::$app->getAddresses();
        $countryCodes = array_keys($addressesService->getCountryRepository()->getList());
        $rules[] = [['countryCode'], 'in', 'range' => $countryCodes];

        foreach (self::_addressAttributes() as $attr) {
            if ($attr === 'countryCode') {
                continue;
            }

            // Add them as individual rows making it easier to extend/manipulate the rules.
            $rules[] = [
                $attr,
                'required',
                'on' => self::SCENARIO_LIVE,
                'when' => function(Address $model, string $attribute) use ($addressesService) {
                    $formatter = $addressesService->getAddressFormatRepository()->get($this->countryCode);
                    return in_array($attribute, $formatter->getRequiredFields());
                },
            ];
        }

        $requirableNativeFields = [
            OrganizationField::class,
            OrganizationTaxIdField::class,
            FullNameField::class,
            LatLongField::class,
        ];

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $fieldLayout = $this->getFieldLayout();

        foreach ($requirableNativeFields as $class) {
            /** @var BaseNativeField|null $field */
            $field = $fieldLayout->getFirstVisibleElementByType($class, $this);
            if ($field && $field->required) {
                $attribute = $field->attribute();
                switch ($attribute) {
                    case 'latLong':
                        $attribute = ['latitude', 'longitude'];
                        break;
                    case 'fullName':
                        if ($generalConfig->showFirstAndLastNameFields) {
                            $attribute = ['firstName', 'lastName'];
                        }
                        break;
                }

                $rules[] = [$attribute, 'required', 'on' => self::SCENARIO_LIVE];
            }
        }

        $rules[] = ['latitude', 'number', 'min' => -90, 'max' => 90, 'on' => [self::SCENARIO_LIVE, self::SCENARIO_DEFAULT]];
        $rules[] = ['longitude', 'number', 'min' => -180, 'max' => 180, 'on' => [self::SCENARIO_LIVE, self::SCENARIO_DEFAULT]];

        $rules[] = [self::_addressAttributes(), 'safe'];

        if ($generalConfig->showFirstAndLastNameFields) {
            $rules[] = [['firstName', 'lastName'], 'safe'];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if (isset($this->fieldId)) {
            $tags[] = "field:$this->fieldId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // commerceguys/addressing 2.0.x - remap changed subdivision IDs
        // update the subdivision ID to its ISO code where available
        if (isset($this->countryCode)) {
            if (isset($this->administrativeArea)) {
                $this->administrativeArea = SubdivisionUpdater::updateValue(
                    $this->countryCode,
                    $this->administrativeArea,
                );
            }
            // Andorra is the only country with remapped localities.
            if ($this->countryCode == 'AD' && isset($this->locality)) {
                $this->locality = SubdivisionUpdater::updateValue(
                    $this->countryCode,
                    $this->locality,
                );
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = AddressRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException("Invalid address ID: $this->id");
            }
        } else {
            $record = new AddressRecord();
            $record->id = $this->id;
        }

        $this->prepareNamesForSave();

        $record->fieldId = $this->fieldId;
        $record->primaryOwnerId = $this->getPrimaryOwnerId();
        $record->countryCode = $this->countryCode;
        $record->administrativeArea = $this->administrativeArea;
        $record->locality = $this->locality;
        $record->dependentLocality = $this->dependentLocality;
        $record->postalCode = $this->postalCode;
        $record->sortingCode = $this->sortingCode;
        $record->addressLine1 = $this->addressLine1;
        $record->addressLine2 = $this->addressLine2;
        $record->addressLine3 = $this->addressLine3;
        $record->organization = $this->organization;
        $record->organizationTaxId = $this->organizationTaxId;
        $record->fullName = $this->fullName;
        $record->firstName = $this->firstName;
        $record->lastName = $this->lastName;
        $record->latitude = $this->latitude;
        $record->longitude = $this->longitude;

        // Capture the dirty attributes from the record
        $dirtyAttributes = array_keys($record->getDirtyAttributes());
        $record->save(false);
        $this->setDirtyAttributes($dirtyAttributes);

        $this->saveOwnership($isNew, Table::ADDRESSES);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->getAddresses()->getFieldLayout();
    }
}
