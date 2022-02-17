<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use CommerceGuys\Addressing\AddressFormat\DependentLocalityType;
use CommerceGuys\Addressing\AddressFormat\LocalityType;
use CommerceGuys\Addressing\AddressFormat\PostalCodeType;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Formatter\FormatterInterface;
use CommerceGuys\Addressing\Formatter\PostalLabelFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Craft;
use craft\commerce\records\Address as AddressRecord;
use craft\db\Table;
use craft\elements\Address;
use craft\events\AddressEvent;
use craft\events\ConfigEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use yii\base\Component;
use yii\base\Exception;

/**
 * Addresses service.
 * An instance of the Addresses service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAddresses()|`Craft::$app->addresses`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read \CommerceGuys\Addressing\AddressFormat\AddressFormatRepository $addressFormatRepository
 * @property-read \CommerceGuys\Addressing\Country\CountryRepository $countryRepository
 * @property-read \CommerceGuys\Addressing\Subdivision\SubdivisionRepository $subdivisionRepository
 */
class Addresses extends Component
{
    /**
     * @event AddressEvent The event that is triggered before an address is saved.
     */
    public const EVENT_BEFORE_SAVE_ADDRESS = 'beforeSaveAddress';

    /**
     * @event AddressEvent The event that is triggered after an address is saved.
     */
    public const EVENT_AFTER_SAVE_ADDRESS = 'afterSaveAddress';

    /**
     * @event AddressEvent The event that is triggered before an address is deleted.
     */
    public const EVENT_BEFORE_DELETE_ADDRESS = 'beforeDeleteAddress';

    /**
     * @event AddressEvent The event that is triggered after an address is saved.
     */
    public const EVENT_AFTER_DELETE_ADDRESS = 'afterDeleteAddress';

    /**
     * @var CountryRepository
     */
    public CountryRepository $_countryRepository;

    /**
     * @var SubdivisionRepository
     */
    public SubdivisionRepository $_subdivisionRepository;

    /**
     * @var AddressFormatRepository
     */
    public AddressFormatRepository $_addressFormatRepository;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->_countryRepository = new CountryRepository();
        $this->_subdivisionRepository = new SubdivisionRepository();
        $this->_addressFormatRepository = new AddressFormatRepository();
    }

    /**
     * @return CountryRepository
     */
    public function getCountryRepository(): CountryRepository
    {
        return $this->_countryRepository;
    }

    /**
     * @return SubdivisionRepository
     */
    public function getSubdivisionRepository(): SubdivisionRepository
    {
        return $this->_subdivisionRepository;
    }

    /**
     * @return AddressFormatRepository
     */
    public function getAddressFormatRepository(): AddressFormatRepository
    {
        return $this->_addressFormatRepository;
    }

    /**
     * Returns an address by its ID.
     *
     * @param int $id
     * @return Address|null
     */
    public function getAddressById(int $id): ?Address
    {
        return Address::findOne($id);
    }

    /**
     * Saves an address.
     *
     * @param Address $address The address to be saved
     * @param bool $runValidation Whether the address should be validated
     * @return bool Whether the address was saved successfully
     */
    public function saveAddress(Address $address, bool $runValidation = true): bool
    {
        $isNewAddress = !$address->id;

        // Fire a 'beforeSaveAddress' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ADDRESS)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ADDRESS, new AddressEvent([
                'address' => $address,
                'isNew' => $isNewAddress,
            ]));
        }

        if ($isNewAddress) {
            $addressRecord = new AddressRecord();
        } else {
            $addressRecord = AddressRecord::findOne($address->id);

            if (!$addressRecord) {
                throw new Exception(Craft::t('commerce', 'No address exists with the ID “{id}”',
                    ['id' => $address->id]));
            }
        }

        if ($runValidation && !$address->validate()) {
            Craft::info('Address not saved due to validation error.', __METHOD__);
            return false;
        }

        $addressRecord->save(false);
        $address->id = $addressRecord->id;

        // Fire a 'afterSaveAddress' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ADDRESS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ADDRESS, new AddressEvent([
                'address' => $address,
            ]));
        }

        return true;
    }

    /**
     * Deletes an address by its ID.
     *
     * @param int $addressId
     * @return bool Whether the address was deleted successfully
     */
    public function deleteAddressById(int $addressId): bool
    {
        $address = $this->getAddressById($addressId);

        // Doesn't exist in the database
        if (!$address) {
            return false;
        }

        return $this->deleteAddress($address);
    }

    /**
     * @param Address $address
     * @return bool Whether the address was deleted successfully
     */
    public function deleteAddress(Address $address): bool
    {
        // Doesn't exist in the database
        if (!$address->id) {
            return false;
        }

        // Fire a 'beforeDeleteFieldLayout' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ADDRESS)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ADDRESS, new AddressEvent([
                'address' => $address,
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::ADDRESSES, ['id' => $address->id])
            ->execute();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ADDRESS)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ADDRESS, new AddressEvent([
                'address' => $address,
            ]));
        }

        return true;
    }

    /**
     * Formats the address model into the correct sequence and format in HTML.
     *
     * @param Address $address
     * @param array $options
     * @param FormatterInterface|null $formatter
     * @return string
     */
    public function formatAddress(Address $address, array $options = [], FormatterInterface $formatter = null): string
    {
        if (!isset($options['locale'])) {
            $options['locale'] = Craft::$app->language;
        }

        if ($formatter === null) {
            $formatter = new DefaultFormatter(
                $this->addressFormatRepository,
                $this->countryRepository,
                $this->subdivisionRepository
            );
        }

        return $formatter->format($address, $options);
    }

    /**
     * Formats the address model into the correct format for a postage label in plain text.
     *
     * @param Address $address
     * @return string
     */
    public function formatAddressPostalLabel(Address $address, array $options = []): string
    {
        $postalLabelFormatter = new PostalLabelFormatter(
            $this->addressFormatRepository,
            $this->countryRepository,
            $this->subdivisionRepository
        );

        return $this->formatAddress($address, $options, $postalLabelFormatter);
    }

    /**
     * @param $type
     * @return string
     */
    public function getLocalityTypeLabel($type): string
    {
        switch ($type) {
            case LocalityType::SUBURB:
                return Craft::t('app', 'Suburb');
            case LocalityType::DISTRICT:
                return Craft::t('app', 'District');
            case LocalityType::CITY:
                return Craft::t('app', 'City');
            case LocalityType::POST_TOWN:
                return Craft::t('app', 'Post Town');
            default:
                // \CommerceGuys\Addressing\AddressFormat\LocalityType::getDefault() is Suburb
                return Craft::t('app', 'City');
        }
    }

    /**
     * @param $type
     * @return string
     */
    public function getDependentLocalityTypeLabel($type): string
    {
        switch ($type) {
            case DependentLocalityType::DISTRICT:
                return Craft::t('app', 'District');
            case DependentLocalityType::NEIGHBORHOOD:
                return Craft::t('app', 'Neighborhood');
            case DependentLocalityType::SUBURB:
                return Craft::t('app', 'Suburb');
            case DependentLocalityType::TOWNLAND:
                return Craft::t('app', 'Townland');
            case DependentLocalityType::VILLAGE_TOWNSHIP:
                return Craft::t('app', 'Village/Township');
            default:
                // \CommerceGuys\Addressing\AddressFormat\DependentLocalityType::getDefault() is Suburb
                return Craft::t('app', 'Suburb');
        }
    }

    /**
     * @param $type
     * @return string
     */
    public function getPostalCodeTypeLabel($type): string
    {
        switch ($type) {
            case PostalCodeType::EIR:
                return Craft::t('app', 'Eircode');
            case PostalCodeType::PIN:
                return Craft::t('app', 'Pin');
            case PostalCodeType::POSTAL:
                return Craft::t('app', 'Postal Code');
            case PostalCodeType::ZIP:
                return Craft::t('app', 'Zip Code');
            default:
                // \CommerceGuys\Addressing\AddressFormat\PostalCodeType::getDefault() is Postal Code
                return Craft::t('app', 'Postal Code');
        }
    }

    /**
     * @param $type
     * @return string
     */
    public function getAdministrativeAreaTypeLabel($type): string
    {
        switch ($type) {
            case AdministrativeAreaType::AREA:
                return Craft::t('app', 'Area');
            case AdministrativeAreaType::CANTON:
                return Craft::t('app', 'Canton');
            case AdministrativeAreaType::COUNTY:
                return Craft::t('app', 'Country');
            case AdministrativeAreaType::DEPARTMENT:
                return Craft::t('app', 'Department');
            case AdministrativeAreaType::DISTRICT:
                return Craft::t('app', 'District');
            case AdministrativeAreaType::DO_SI:
                return Craft::t('app', 'Do Si');
            case AdministrativeAreaType::EMIRATE:
                return Craft::t('app', 'Emirate');
            case AdministrativeAreaType::ISLAND:
                return Craft::t('app', 'Island');
            case AdministrativeAreaType::OBLAST:
                return Craft::t('app', 'Oblast');
            case AdministrativeAreaType::PARISH:
                return Craft::t('app', 'Parish');
            case AdministrativeAreaType::PREFECTURE:
                return Craft::t('app', 'Prefecture');
            case AdministrativeAreaType::PROVINCE:
                return Craft::t('app', 'Province');
            case AdministrativeAreaType::STATE:
                return Craft::t('app', 'State');
            default:
                // \CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType::getDefault() is Province
                return Craft::t('app', 'Province');
        }
    }

    /**
     * Save the address field layout
     *
     * @param FieldLayout $layout
     * @param bool $runValidation Whether the layout should be validated
     * @return bool
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if ($runValidation && !$layout->validate()) {
            Craft::info('Field layout not saved due to validation error.', __METHOD__);
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayoutConfig = $layout->getConfig();
        $uid = StringHelper::UUID();

        $projectConfig->set(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, [$uid => $fieldLayoutConfig], "Save the address field layout");
        return true;
    }

    /**
     * Handle address field layout changes.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedAddressFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

        $fieldsService = Craft::$app->getFields();

        if (empty($data) || empty($config = reset($data))) {
            $fieldsService->deleteLayoutsByType(Address::class);
            return;
        }

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        // Save the field layout
        $layout = FieldLayout::createFromConfig($config);
        $layout->id = $fieldsService->getLayoutByType(Address::class)->id;
        $layout->type = Address::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(Address::class);
    }
}
