<?php

namespace craft\elements;

use CommerceGuys\Addressing\AddressInterface;
use Craft;
use craft\base\BlockElementInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\NameTrait;
use craft\elements\conditions\addresses\AddressCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQueryInterface;
use craft\fieldlayoutelements\addresses\LatLongField;
use craft\fieldlayoutelements\addresses\OrganizationField;
use craft\fieldlayoutelements\addresses\OrganizationTaxIdField;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\FullNameField;
use craft\models\FieldLayout;
use craft\records\Address as AddressRecord;
use yii\base\InvalidConfigException;

/**
 * Address element class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Element implements AddressInterface, BlockElementInterface
{
    use NameTrait;

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
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inerhitdoc
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
     * @return AddressQuery The newly created [[AddressQuery]] instance.
     */
    public static function find(): ElementQueryInterface
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
     */
    public static function addressAttributeLabel(string $attribute, string $countryCode): ?string
    {
        if (in_array($attribute, [
            'administrativeArea',
            'locality',
            'dependentLocality',
            'postalCode',
        ], true)) {
            $formatRepo = Craft::$app->getAddresses()->getAddressFormatRepository()->get($countryCode);
            return match ($attribute) {
                'administrativeArea' => Craft::$app->getAddresses()->getAdministrativeAreaTypeLabel($formatRepo->getAdministrativeAreaType()),
                'locality' => Craft::$app->getAddresses()->getLocalityTypeLabel($formatRepo->getLocalityType()),
                'dependentLocality' => Craft::$app->getAddresses()->getDependentLocalityTypeLabel($formatRepo->getDependentLocalityType()),
                'postalCode' => Craft::$app->getAddresses()->getPostalCodeTypeLabel($formatRepo->getPostalCodeType()),
            };
        }

        return match ($attribute) {
            'countryCode' => Craft::t('app', 'Country'),
            'sortingCode' => Craft::t('app', 'Sorting Code'),
            'addressLine1' => Craft::t('app', 'Address Line 1'),
            'addressLine2' => Craft::t('app', 'Address Line 2'),
            default => null,
        };
    }

    /**
     * @inheritdoc
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'Address';
    }

    /**
     * @var int|null Owner ID
     */
    public ?int $ownerId = null;

    /**
     * @var ElementInterface|null The owner element
     * @see getOwner()
     */
    private ?ElementInterface $_owner = null;

    /**
     * @var string Two-letter country code
     * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
     */
    public string $countryCode = 'US';

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

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabel($attribute): string
    {
        return match ($attribute) {
            'title' => Craft::t('app', 'Label'),
            'organization' => Craft::t('app', 'Organization'),
            'organizationTaxId' => Craft::t('app', 'Organization Tax ID'),
            'fullName' => Craft::t('app', 'Full Name'),
            'firstName' => Craft::t('app', 'First Name'),
            'lastName' => Craft::t('app', 'Last Name'),
            'latitude' => Craft::t('app', 'Latitude'),
            'longitude' => Craft::t('app', 'Longitude'),
            default => static::addressAttributeLabel($attribute, $this->countryCode) ?? parent::getAttributeLabel($attribute),
        };
    }

    /**
     * @inheritdoc
     */
    public function getOwner(): ?ElementInterface
    {
        if (!isset($this->ownerId)) {
            return null;
        }

        if (!isset($this->_owner)) {
            $owner = Craft::$app->getElements()->getElementById($this->ownerId);
            if ($owner === null) {
                throw new InvalidConfigException("Invalid owner ID: $this->ownerId");
            }
            $this->_owner = $owner;
        }

        return $this->_owner;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return (
            parent::canView($user) ||
            ($this->getOwner()?->getCanonical(true)->canView($user) ?? false)
        );
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        return (
            parent::canSave($user) ||
            ($this->getOwner()?->getcanonical(true)->canSave($user) ?? false)
        );
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return (
            parent::canDelete($user) ||
            ($this->getOwner()?->getCanonical(true)->canSave($user) ?? false)
        );
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
        return 'und';
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        $formatter = Craft::$app->getAddresses()->getAddressFormatRepository()->get($this->countryCode);
        $usedFields = array_unique([
            ...$formatter->getUsedFields(),
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
        $rules[] = [['ownerId'], 'number'];
        $rules[] = [['countryCode'], 'required'];

        foreach (self::_addressAttributes() as $attr) {
            if ($attr === 'countryCode') {
                continue;
            }

            // Add them as individual rows making it easier to extend/manipulate the rules.
            $rules[] = [
                $attr,
                'required',
                'on' => self::SCENARIO_LIVE,
                'when' => function(Address $model, string $attribute) {
                    $formatter = Craft::$app->getAddresses()->getAddressFormatRepository()->get($this->countryCode);
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

        $fieldLayout = $this->getFieldLayout();

        foreach ($requirableNativeFields as $class) {
            /** @var BaseNativeField|null $field */
            $field = $fieldLayout->getFirstVisibleElementByType($class, $this);
            if ($field && $field->required) {
                $attribute = $field->attribute();
                if ($attribute === 'latLong') {
                    $attribute = ['latitude', 'longitude'];
                }
                $rules[] = [$attribute, 'required', 'on' => self::SCENARIO_LIVE];
            }
        }

        $rules[] = [['longitude', 'latitude'], 'safe'];
        $rules[] = [self::_addressAttributes(), 'safe'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->ownerId) {
            $tags[] = "owner:$this->ownerId";
        }

        return $tags;
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

        $record->ownerId = $this->ownerId;
        $record->countryCode = $this->countryCode;
        $record->administrativeArea = $this->administrativeArea;
        $record->locality = $this->locality;
        $record->dependentLocality = $this->dependentLocality;
        $record->postalCode = $this->postalCode;
        $record->sortingCode = $this->sortingCode;
        $record->addressLine1 = $this->addressLine1;
        $record->addressLine2 = $this->addressLine2;
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

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->getAddresses()->getLayout();
    }
}
