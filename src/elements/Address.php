<?php

namespace craft\elements;

use Craft;
use CommerceGuys\Addressing\AddressInterface;
use craft\base\Element;
use craft\commerce\fieldlayoutelements\VariantsField;
use craft\fieldlayoutelements\AddressField;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\records\Address as AddressRecord;
use yii\base\Exception;

/**
 * Address model class
 *
 * @property string $countryCode        The two-letter country code.
 * @property string $administrativeArea The administrative area.
 * @property string $locality           The locality.
 * @property string $dependentLocality  The dependent locality.
 * @property string $postalCode         The postal code.
 * @property string $sortingCode        The sorting code
 * @property string $addressLine1       The first line of the address block.
 * @property string $addressLine2       The second line of the address block.
 * @property string $organization       The organization.
 * @property string $givenName          The given name.
 * @property string $additionalName     The additional name.
 * @property string $familyName         The family name.
 * @property array $metadata            The metadata attached to the address. Should only be key value pairs.
 * @property string $label              The label to identify this address to the person who created it.
 * @property string $locale             The locale. Defaults to 'und'.
 * @property-read bool $isEmpty         Whether the address is empty.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Element implements AddressInterface
{
    /**
     * @var ?int ID
     */
    public ?int $id = null;

    /**
     * @inheritdoc
     */
    private $_countryCode;

    /**
     * @inheritdoc
     */
    private $_administrativeArea;

    /**
     * @inheritdoc
     */
    private $_locality;

    /**
     * @inheritdoc
     */
    private $_dependentLocality;

    /**
     * @inheritdoc
     */
    private $_postalCode;

    /**
     * @inheritdoc
     */
    private $_sortingCode;

    /**
     * @inheritdoc
     */
    private $_addressLine1;

    /**
     * @inheritdoc
     */
    private $_addressLine2;

    /**
     * @inheritdoc
     */
    private $_organization;

    /**
     * @inheritdoc
     */
    private $_givenName;

    /**
     * @inheritdoc
     */
    private $_additionalName;

    /**
     * @inheritdoc
     */
    private $_familyName;

    /**
     * @inheritdoc
     */
    private $_locale;

    /**
     * L
     */
    private string $_label = '';

    /**
     *
     */
    private $_metadata;

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
            'metadata',
        ];
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
        return $this->getAttributes();
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
     * @param string $administrativeArea
     */
    public function setAdministrativeArea(string $administrativeArea): void
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
     * @param string $locality
     */
    public function setLocality(string $locality): void
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
     * @param string $dependentLocality
     */
    public function setDependentLocality(string $dependentLocality): void
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
     * @param string $postalCode
     */
    public function setPostalCode(string $postalCode): void
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
     * @param string $sortingCode
     */
    public function setSortingCode(string $sortingCode): void
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
     * @param string $addressLine1
     */
    public function setAddressLine1(string $addressLine1): void
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
     * @param string $addressLine2
     */
    public function setAddressLine2(string $addressLine2): void
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
     * @param string $organization
     */
    public function setOrganization(string $organization): void
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
     * @param string $givenName
     */
    public function setGivenName(string $givenName): void
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
     * @param string $additionalName
     */
    public function setAdditionalName(string $additionalName): void
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
     * @param string $familyName
     */
    public function setFamilyName(string $familyName): void
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
     * @return string Label
     */
    public function getLabel(): string
    {
        return $this->_label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label = ''): void
    {
        $this->_label = $label;
    }

    /**
     * @return array Metadata
     */
    public function getMetadata(): array
    {
        return $this->_metadata ?? [];
    }

    /**
     * @param string|array Metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->_metadata = Json::decodeIfJson($metadata);
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['countryCode'], 'required'];

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
        return \Craft::$app->getAddresses()->formatAddressPostalLabel($this);
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

        $record->label = $this->label;
        $record->countryCode = $this->countryCode;
        $record->administrativeArea = $this->administrativeArea;
        $record->locality = $this->locality;
        $record->dependentLocality = $this->dependentLocality;
        $record->postalCode = $this->postalCode;
        $record->sortingCode = $this->sortingCode;
        $record->addressLine1 = $this->addressLine1;
        $record->addressLine2 = $this->addressLine2;
        $record->organization = $this->organization;
        $record->givenName = $this->givenName;
        $record->additionalName = $this->additionalName;
        $record->familyName = $this->familyName;
        $record->metadata = $this->metadata;

        // Capture the dirty attributes from the record
        $dirtyAttributes = array_keys($record->getDirtyAttributes());

        $record->save(false);

        $this->setDirtyAttributes($dirtyAttributes);

        parent::afterSave($isNew);
    }
}
