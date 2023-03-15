<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use CommerceGuys\Addressing\AddressFormat\DependentLocalityType;
use CommerceGuys\Addressing\AddressFormat\LocalityType;
use CommerceGuys\Addressing\AddressFormat\PostalCodeType;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Formatter\FormatterInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Craft;
use craft\elements\Address;
use craft\events\ConfigEvent;
use craft\events\DefineAddressFieldLabelEvent;
use craft\events\DefineAddressFieldsEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use yii\base\Component;

/**
 * Addresses service.
 * An instance of the Addresses service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAddresses()|`Craft::$app->addresses`]].
 *
 * @property-read AddressFormatRepository $addressFormatRepository
 * @property-read CountryRepository $countryRepository
 * @property-read SubdivisionRepository $subdivisionRepository
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Addresses extends Component
{
    /**
     * @event DefineAddressFieldsEvent The event that is triggered when defining the address fields that are used by a given country code.
     * @see getUsedFields()
     * @since 4.3.0
     */
    public const EVENT_DEFINE_USED_FIELDS = 'defineUsedFields';

    /**
     * @event DefineAddressFieldsEvent The event that is triggered when defining the subdivision address fields that are used by a given country code.
     * @see getUsedSubdivisionFields()
     * @since 4.3.0
     */
    public const EVENT_DEFINE_USED_SUBDIVISION_FIELDS = 'defineUsedSubdivisionFields';

    /**
     * @event DefineAddressFieldLabelEvent The event that is triggered when defining the label of an address field for a given country code.
     * @see getFieldLabel()
     * @since 4.3.0
     */
    public const EVENT_DEFINE_FIELD_LABEL = 'defineFieldLabel';

    /**
     * @var CountryRepository
     */
    private CountryRepository $_countryRepository;

    /**
     * @var SubdivisionRepository
     */
    private SubdivisionRepository $_subdivisionRepository;

    /**
     * @var AddressFormatRepository
     */
    private AddressFormatRepository $_addressFormatRepository;

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
     * Returns the address fields that are used by a given country code.
     *
     * @param string $countryCode
     * @return string[]
     * @see AddressField
     * @since 4.3.0
     */
    public function getUsedFields(string $countryCode): array
    {
        $fields = $this->getAddressFormatRepository()->get($countryCode)->getUsedFields();

        if ($this->hasEventHandlers(self::EVENT_DEFINE_USED_FIELDS)) {
            $event = new DefineAddressFieldsEvent([
                'countryCode' => $countryCode,
                'fields' => $fields,
            ]);
            $this->trigger(self::EVENT_DEFINE_USED_FIELDS, $event);
            return $event->fields;
        }

        return $fields;
    }

    /**
     * Returns the subdivision address fields that are used by a given country code.
     *
     * @param string $countryCode
     * @return string[]
     * @see AddressField
     * @since 4.3.0
     */
    public function getUsedSubdivisionFields(string $countryCode): array
    {
        $fields = $this->getAddressFormatRepository()->get($countryCode)->getUsedSubdivisionFields();

        if ($this->hasEventHandlers(self::EVENT_DEFINE_USED_SUBDIVISION_FIELDS)) {
            $event = new DefineAddressFieldsEvent([
                'countryCode' => $countryCode,
                'fields' => $fields,
            ]);
            $this->trigger(self::EVENT_DEFINE_USED_SUBDIVISION_FIELDS, $event);
            return $event->fields;
        }

        return $fields;
    }

    /**
     * Returns the user-facing label for an address field, for a given country code.
     *
     * @param string $field One of the [[AddressField]] class constants
     * @phpstan-param AddressField::* $field
     * @param string $countryCode
     * @return string
     * @since 4.3.0
     */
    public function getFieldLabel(string $field, string $countryCode): string
    {
        $label = match ($field) {
            AddressField::ADMINISTRATIVE_AREA => $this->getAdministrativeAreaTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getAdministrativeAreaType()),
            AddressField::LOCALITY => $this->getLocalityTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getLocalityType()),
            AddressField::DEPENDENT_LOCALITY => $this->getDependentLocalityTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getDependentLocalityType()),
            AddressField::POSTAL_CODE => $this->getPostalCodeTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getPostalCodeType()),
            AddressField::SORTING_CODE => Craft::t('app', 'Sorting Code'),
            AddressField::ADDRESS_LINE1 => Craft::t('app', 'Address Line 1'),
            AddressField::ADDRESS_LINE2 => Craft::t('app', 'Address Line 2'),
            AddressField::ORGANIZATION => Craft::t('app', 'Organization'),
            AddressField::GIVEN_NAME => Craft::t('app', 'First Name'),
            AddressField::ADDITIONAL_NAME => 'Additional Name', // Unused in Craft
            AddressField::FAMILY_NAME => Craft::t('app', 'Last Name'),
        };

        if ($this->hasEventHandlers(self::EVENT_DEFINE_FIELD_LABEL)) {
            $event = new DefineAddressFieldLabelEvent([
                'countryCode' => $countryCode,
                'field' => $field,
                'label' => $label,
            ]);
            $this->trigger(self::EVENT_DEFINE_FIELD_LABEL, $event);
            return $event->label;
        }

        return $label;
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
                $this->getAddressFormatRepository(),
                $this->getCountryRepository(),
                $this->getSubdivisionRepository()
            );
        }

        return $formatter->format($address, $options);
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getLocalityTypeLabel(?string $type): string
    {
        return match ($type) {
            LocalityType::SUBURB => Craft::t('app', 'Suburb'),
            LocalityType::DISTRICT => Craft::t('app', 'District'),
            LocalityType::POST_TOWN => Craft::t('app', 'Post Town'),
            default => Craft::t('app', 'City'),
        };
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getDependentLocalityTypeLabel(?string $type): string
    {
        return match ($type) {
            DependentLocalityType::DISTRICT => Craft::t('app', 'District'),
            DependentLocalityType::NEIGHBORHOOD => Craft::t('app', 'Neighborhood'),
            DependentLocalityType::TOWNLAND => Craft::t('app', 'Townland'),
            DependentLocalityType::VILLAGE_TOWNSHIP => Craft::t('app', 'Village/Township'),
            default => Craft::t('app', 'Suburb'),
        };
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getPostalCodeTypeLabel(?string $type): string
    {
        return match ($type) {
            PostalCodeType::EIR => Craft::t('app', 'Eircode'),
            PostalCodeType::PIN => Craft::t('app', 'Pin'),
            PostalCodeType::ZIP => Craft::t('app', 'Zip Code'),
            default => Craft::t('app', 'Postal Code'),
        };
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getAdministrativeAreaTypeLabel(?string $type): string
    {
        return match ($type) {
            AdministrativeAreaType::AREA => Craft::t('app', 'Area'),
            AdministrativeAreaType::CANTON => Craft::t('app', 'Canton'),
            AdministrativeAreaType::COUNTY => Craft::t('app', 'County'),
            AdministrativeAreaType::DEPARTMENT => Craft::t('app', 'Department'),
            AdministrativeAreaType::DISTRICT => Craft::t('app', 'District'),
            AdministrativeAreaType::DO_SI => Craft::t('app', 'Do Si'),
            AdministrativeAreaType::EMIRATE => Craft::t('app', 'Emirate'),
            AdministrativeAreaType::ISLAND => Craft::t('app', 'Island'),
            AdministrativeAreaType::OBLAST => Craft::t('app', 'Oblast'),
            AdministrativeAreaType::PARISH => Craft::t('app', 'Parish'),
            AdministrativeAreaType::PREFECTURE => Craft::t('app', 'Prefecture'),
            AdministrativeAreaType::STATE => Craft::t('app', 'State'),
            default => Craft::t('app', 'Province'),
        };
    }

    /**
     * Returns the address field layout.
     *
     * @return FieldLayout
     */
    public function getLayout(): FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(Address::class);

        // Ensure it has at least one tab.
        // (The only reason this could possibly be null is if a module is removing all our own native fields
        // via EVENT_DEFINE_NATIVE_FIELDS.)
        $firstTab = $fieldLayout->getTabs()[0] ?? null;
        if (!$firstTab) {
            $firstTab = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => Craft::t('app', 'Content'),
            ]);
            $fieldLayout->setTabs([$firstTab]);
        }

        return $fieldLayout;
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

        $projectConfig->set(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, [$uid => $fieldLayoutConfig], 'Save the address field layout');
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
        $layout->id = $this->getLayout()->id;
        $layout->type = Address::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(Address::class);
    }
}
