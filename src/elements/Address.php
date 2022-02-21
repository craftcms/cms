<?php

namespace craft\elements;

use CommerceGuys\Addressing\AddressInterface;
use Craft;
use craft\base\Element;
use craft\elements\conditions\addresses\AddressCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\records\Address as AddressRecord;
use craft\validators\RequiredFieldAddressValidator;
use yii\base\Exception;

/**
 * Address element class
 *
 * @property string $countryCode The two-letter country code.
 * @property string $administrativeArea The administrative area.
 * @property string $locality The locality.
 * @property string $dependentLocality The dependent locality.
 * @property string $postalCode The postal code.
 * @property string $sortingCode The sorting code
 * @property string $addressLine1 The first line of the address block.
 * @property string $addressLine2 The second line of the address block.
 * @property string $organization The organization.
 * @property string $givenName The given name.
 * @property string $additionalName The additional name.
 * @property string $familyName The family name.
 * @property string $latitude The latitude of the address.
 * @property string $longitude The longitude of the address.
 * @property string $label The label to identify this address to the person who created it.
 * @property string $locale The locale. Defaults to 'und'.
 * @property-read bool $isEmpty Whether the address is empty.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Element implements AddressInterface
{
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
        return false;
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
     * @param $config
     * @return Address
     */
    public static function create($config): Address
    {
        $config = array_filter($config);

        // Support fields
        $fields = [];
        if (isset($config['fields'])) {
            $fields = $config['fields'];
            unset($config['fields']);
        }

        $address = new static($config);
        $address->uid = $address->uid ?: StringHelper::UUID();
        $address->setFieldValues($fields);
        return $address;
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
        return self::_attributes();
    }

    /**
     * Returns all attributes
     *
     * @return string[]
     */
    private static function _attributes(): array
    {
        return [
            'id',
            'label',
            'countryCode',
            'givenName',
            'additionalName',
            'familyName',
            'addressLine1',
            'addressLine2',
            'administrativeArea',
            'locality',
            'dependentLocality',
            'postalCode',
            'sortingCode',
            'organization',
            'latitude',
            'longitude'
        ];
    }

    /**
     * @var string The two-letter country code.
     * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
     * @see getCountryCode()
     * @see setCountryCode()
     */
    private string $_countryCode = 'US';

    /**
     * @var string|null The administrative area.
     * @see getAdministrativeArea()
     * @see setAdministrativeArea()
     */
    private ?string $_administrativeArea = null;

    /**
     * @var string|null The locality.
     * @see getLocality()
     * @see setLocality()
     */
    private ?string $_locality = null;

    /**
     * @var string|null The dependent locality.
     * @see getDependentLocality()
     * @see setDependentLocality()
     */
    private ?string $_dependentLocality = null;

    /**
     * @var string|null The postal code.
     * @see getPostalCode()
     * @see setPostalCode()
     */
    private ?string $_postalCode = null;

    /**
     * @var string|null The sorting code.
     * @see getSortingCode()
     * @see setSortingCode()
     */
    private ?string $_sortingCode = null;

    /**
     * @var string|null The first line of the address.
     * @see getAddressLine1()
     * @see setAddressLine1()
     */
    private ?string $_addressLine1 = null;

    /**
     * @var string|null The second line of the address.
     * @see getAddressLine2()
     * @see setAddressLine2()
     */
    private ?string $_addressLine2 = null;

    /**
     * @var string|null The organization.
     * @see getOrganization()
     * @see setOrganization()
     */
    private ?string $_organization = null;

    /**
     * @var string|null The given name.
     * @see getGivenName()
     * @see setGivenName()
     */
    private ?string $_givenName = null;

    /**
     * @var string|null The additional name.
     * @see getAdditionalName()
     * @see setAdditionalName()
     */
    private ?string $_additionalName = null;

    /**
     * @var string|null The family name.
     * @see getFamilyName()
     * @see setFamilyName()
     */
    private ?string $_familyName = null;

    /**
     * @var string|null The locale.
     * @see getLocale()
     * @see setLocale()
     */
    private ?string $_locale = null;

    /**
     * @var string|null The label to identify this address to the person who created it.
     * @see getLabel()
     * @see setLabel()
     */
    private ?string $_label = null;

    /**
     * @var string|null The Latitude.
     * @see getLatitude()
     * @see setLatitude()
     */
    private ?string $_latitude = null;

    /**
     * @var string The Longitude.
     * @see getLongitude()
     * @see setLongitude()
     */
    private ?string $_longitude = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Default local in addressing for 'all locales'
        if (!$this->_locale) {
            $this->_locale = 'und';
        }
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabel($attribute): string
    {
        $formatRepo = Craft::$app->getAddresses()->getAddressFormatRepository()->get($this->getCountryCode());
        if (in_array($attribute, ['dependentLocality', 'locality', 'postalCode', 'administrativeArea'])) {
            return match ($attribute) {
                'dependentLocality' => Craft::$app->getAddresses()->getDependentLocalityTypeLabel($formatRepo->getDependentLocalityType()),
                'locality' => Craft::$app->getAddresses()->getLocalityTypeLabel($formatRepo->getLocalityType()),
                'postalCode' => Craft::$app->getAddresses()->getPostalCodeTypeLabel($formatRepo->getPostalCodeType()),
                'administrativeArea' => Craft::$app->getAddresses()->getAdministrativeAreaTypeLabel($formatRepo->getAdministrativeAreaType()),
            };
        }

        return match ($attribute) {
            'label' => Craft::t('app', 'Label'),
            'countryCode' => Craft::t('app', 'Country'),
            'givenName' => Craft::t('app', 'Given name'),
            'familyName' => Craft::t('app', 'Family name'),
            'additionalName' => Craft::t('app', 'Additional name'),
            'organization' => Craft::t('app', 'Organization'),
            'addressLine1' => Craft::t('app', 'Address Line 1'),
            'addressLine2' => Craft::t('app', 'Address Line 2'),
            'sortingCode' => Craft::t('app', 'Sorting code'),
            default => parent::getAttributeLabel($attribute),
        };
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        return self::_attributes();
    }

    /**
     * @inheritdoc
     */
    public function safeAttributes()
    {
        return self::_attributes(); // Currently, all are writable
    }

    /**
     * @inheritdoc
     */
    public function getCountryCode()
    {
        return $this->_countryCode;
    }

    /**
     * @param string $countryCode
     */
    public function setCountryCode(string $countryCode): void
    {
        $this->_countryCode = $countryCode;
    }

    /**
     * @inheritdoc
     */
    public function getAdministrativeArea()
    {
        return $this->_administrativeArea;
    }

    /**
     * @param string|null $administrativeArea
     */
    public function setAdministrativeArea(?string $administrativeArea): void
    {
        $this->_administrativeArea = $administrativeArea;
    }

    /**
     * @inheritdoc
     */
    public function getLocality()
    {
        return $this->_locality;
    }

    /**
     * @param string|null $locality
     */
    public function setLocality(?string $locality): void
    {
        $this->_locality = $locality;
    }

    /**
     * @inheritdoc
     */
    public function getDependentLocality()
    {
        return $this->_dependentLocality;
    }

    /**
     * @param string|null $dependentLocality
     */
    public function setDependentLocality(?string $dependentLocality): void
    {
        $this->_dependentLocality = $dependentLocality;
    }

    /**
     * @inheritdoc
     */
    public function getPostalCode()
    {
        return $this->_postalCode;
    }

    /**
     * @param string|null $postalCode
     */
    public function setPostalCode(?string $postalCode): void
    {
        $this->_postalCode = $postalCode;
    }

    /**
     * @inheritdoc
     */
    public function getSortingCode()
    {
        return $this->_sortingCode;
    }

    /**
     * @param string|null $sortingCode
     */
    public function setSortingCode(?string $sortingCode): void
    {
        $this->_sortingCode = $sortingCode;
    }

    /**
     * @inheritdoc
     */
    public function getAddressLine1()
    {
        return $this->_addressLine1;
    }

    /**
     * @param string|null $addressLine1
     */
    public function setAddressLine1(?string $addressLine1): void
    {
        $this->_addressLine1 = $addressLine1;
    }

    /**
     * @inheritdoc
     */
    public function getAddressLine2()
    {
        return $this->_addressLine2;
    }

    /**
     * @param string|null $addressLine2
     */
    public function setAddressLine2(?string $addressLine2): void
    {
        $this->_addressLine2 = $addressLine2;
    }

    /**
     * @inheritdoc
     */
    public function getOrganization()
    {
        return $this->_organization;
    }

    /**
     * @param string|null $organization
     */
    public function setOrganization(?string $organization): void
    {
        $this->_organization = $organization;
    }

    /**
     * @inheritdoc
     */
    public function getGivenName()
    {
        return $this->_givenName;
    }

    /**
     * @param string|null $givenName
     */
    public function setGivenName(?string $givenName): void
    {
        $this->_givenName = $givenName;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalName()
    {
        return $this->_additionalName;
    }

    /**
     * @param string|null $additionalName
     */
    public function setAdditionalName(?string $additionalName): void
    {
        $this->_additionalName = $additionalName;
    }

    /**
     * @inheritdoc
     */
    public function getFamilyName()
    {
        return $this->_familyName;
    }

    /**
     * @param string|null $familyName
     */
    public function setFamilyName(?string $familyName): void
    {
        $this->_familyName = $familyName;
    }

    /**
     * @inheritdoc
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale = 'und'): void
    {
        $this->_locale = $locale;
    }

    /**
     * @return string|null Label
     */
    public function getLabel(): ?string
    {
        return $this->_label;
    }

    /**
     * @param string|null $label
     */
    public function setLabel(?string $label): void
    {
        $this->_label = $label;
    }

    /**
     * @return string Latitude
     */
    public function getLatitude(): string
    {
        return (string)$this->_latitude;
    }

    /**
     * @param string|null Latitude
     */
    public function setLatitude(?string $latitude): void
    {
        $this->_latitude = $latitude;
    }

    /**
     * @return string Longitude
     */
    public function getLongitude(): string
    {
        return (string)$this->_longitude;
    }

    /**
     * @param string|null Longitude
     */
    public function setLongitude(?string $longitude): void
    {
        $this->_longitude = $longitude;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['countryCode'], 'required'];

        $rules[] = [
            static::_attributes(),
            RequiredFieldAddressValidator::class
        ];

        return $rules;
    }

    /**
     * An address is not empty if it has more than the country and administrative area populated
     *
     * @return bool
     */
    public function getIsEmpty(): bool
    {
        foreach ($this->getAttributes(null, ['countryCode', 'administrativeArea']) as $value) {
            if ($value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return Craft::$app->getAddresses()->formatAddressPostalLabel($this);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = AddressRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid address ID: ' . $this->id);
            }
        } else {
            $record = new AddressRecord();
            $record->id = $this->id;
        }

        $record->label = $this->getLabel();
        $record->countryCode = $this->getCountryCode();
        $record->administrativeArea = $this->getAdministrativeArea();
        $record->locality = $this->getLocality();
        $record->dependentLocality = $this->getDependentLocality();
        $record->postalCode = $this->getPostalCode();
        $record->sortingCode = $this->getSortingCode();
        $record->addressLine1 = $this->getAddressLine1();
        $record->addressLine2 = $this->getAddressLine2();
        $record->organization = $this->getOrganization();
        $record->givenName = $this->getGivenName();
        $record->additionalName = $this->getAdditionalName();
        $record->familyName = $this->getFamilyName();
        $record->latitude = $this->getLatitude();
        $record->longitude = $this->getLongitude();

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
        return Craft::$app->getFields()->getLayoutByType(self::class);
    }
}
