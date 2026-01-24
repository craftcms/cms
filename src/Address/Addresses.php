<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use CommerceGuys\Addressing\AddressFormat\DependentLocalityType;
use CommerceGuys\Addressing\AddressFormat\LocalityType;
use CommerceGuys\Addressing\AddressFormat\PostalCodeType;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Formatter\FormatterInterface;
use Craft;
use craft\base\FieldLayoutProviderInterface;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Events\DefineAddressCountries;
use CraftCms\Cms\Address\Events\DefineAddressFieldLabel;
use CraftCms\Cms\Address\Events\DefineAddressSubdivisions;
use CraftCms\Cms\Address\Events\DefineAddressUsedFields;
use CraftCms\Cms\Address\Events\DefineAddressUsedSubdivisionFields;
use CraftCms\Cms\Address\Repositories\SubdivisionRepository;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;

use function CraftCms\Cms\t;

#[Singleton]
final readonly class Addresses implements FieldLayoutProviderInterface
{
    private FormatterInterface $formatter;

    public function __construct(
        private ProjectConfig $projectConfig,
        private CountryRepository $countryRepository,
        private SubdivisionRepository $subdivisionRepository,
        private AddressFormatRepository $addressFormatRepository,
        private Fields $fields,
        ?FormatterInterface $formatter = null,
    ) {
        $this->formatter = $formatter ?? new DefaultFormatter(
            $this->addressFormatRepository,
            $this->countryRepository,
            $this->subdivisionRepository,
        );
    }

    public function getCountryRepository(): CountryRepository
    {
        return $this->countryRepository;
    }

    public function getSubdivisionRepository(): SubdivisionRepository
    {
        return $this->subdivisionRepository;
    }

    public function getAddressFormatRepository(): AddressFormatRepository
    {
        return $this->addressFormatRepository;
    }

    /**
     * Returns subdivisions for a field based on its parents.
     */
    public function defineAddressSubdivisions(array $parents, array $options = []): array
    {
        event($event = new DefineAddressSubdivisions($parents, $options));

        return $event->subdivisions;
    }

    /**
     * Returns a list of countries to be used as options for selection.
     */
    public function getCountryList(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $countries = $this->countryRepository->getList($locale);

        event($event = new DefineAddressCountries($locale, $countries));

        return $event->countries;
    }

    /**
     * Returns the address fields that are used by a given country code.
     *
     * @return string[]
     *
     * @see AddressField
     */
    public function getUsedFields(string $countryCode): array
    {
        $fields = $this->addressFormatRepository->get($countryCode)->getUsedFields();

        event($event = new DefineAddressUsedFields($countryCode, $fields));

        return $event->fields;
    }

    /**
     * Returns the subdivision address fields that are used by a given country code.
     *
     * @return string[]
     *
     * @see AddressField
     */
    public function getUsedSubdivisionFields(string $countryCode): array
    {
        $fields = $this->addressFormatRepository->get($countryCode)->getUsedSubdivisionFields();

        event($event = new DefineAddressUsedSubdivisionFields($countryCode, $fields));

        return $event->fields;
    }

    /**
     * Returns the user-facing label for an address field, for a given country code.
     *
     * @param  string  $field  One of the [[AddressField]] class constants
     *
     * @phpstan-param AddressField::* $field
     */
    public function getFieldLabel(string $field, string $countryCode): string
    {
        $label = match ($field) {
            AddressField::ADMINISTRATIVE_AREA => $this->getAdministrativeAreaTypeLabel($this->addressFormatRepository->get($countryCode)->getAdministrativeAreaType()),
            AddressField::LOCALITY => $this->getLocalityTypeLabel($this->addressFormatRepository->get($countryCode)->getLocalityType()),
            AddressField::DEPENDENT_LOCALITY => $this->getDependentLocalityTypeLabel($this->addressFormatRepository->get($countryCode)->getDependentLocalityType()),
            AddressField::POSTAL_CODE => $this->getPostalCodeTypeLabel($this->addressFormatRepository->get($countryCode)->getPostalCodeType()),
            AddressField::SORTING_CODE => t('Sorting Code'),
            AddressField::ADDRESS_LINE1 => t('Address Line 1'),
            AddressField::ADDRESS_LINE2 => t('Address Line 2'),
            AddressField::ADDRESS_LINE3 => t('Address Line 3'),
            AddressField::ORGANIZATION => t('Organization'),
            AddressField::GIVEN_NAME => t('First Name'),
            AddressField::ADDITIONAL_NAME => 'Additional Name', // Unused in Craft
            AddressField::FAMILY_NAME => t('Last Name'),
        };

        event($event = new DefineAddressFieldLabel($countryCode, $field, $label));

        return $event->label;
    }

    /**
     * Formats the address model into the correct sequence and format in HTML.
     */
    public function formatAddress(Address $address, array $options = [], ?FormatterInterface $formatter = null): string
    {
        $options['locale'] ??= app()->getLocale();
        $formatter ??= $this->formatter;

        return $formatter->format($address, $options);
    }

    public function getLocalityTypeLabel(?string $type): string
    {
        return match ($type) {
            LocalityType::DISTRICT => t('District'),
            LocalityType::POST_TOWN => t('Post Town'),
            LocalityType::SUBURB => t('Suburb'),
            LocalityType::TOWN_CITY => t('City/Town'),
            default => t('City'),
        };
    }

    public function getDependentLocalityTypeLabel(?string $type): string
    {
        return match ($type) {
            DependentLocalityType::DISTRICT => t('District'),
            DependentLocalityType::NEIGHBORHOOD => t('Neighborhood'),
            DependentLocalityType::TOWNLAND => t('Townland'),
            DependentLocalityType::VILLAGE_TOWNSHIP => t('Village/Township'),
            default => t('Suburb'),
        };
    }

    public function getPostalCodeTypeLabel(?string $type): string
    {
        return match ($type) {
            PostalCodeType::EIR => t('Eircode'),
            PostalCodeType::PIN => t('Pin'),
            PostalCodeType::ZIP => t('Zip Code'),
            default => t('Postal Code'),
        };
    }

    public function getAdministrativeAreaTypeLabel(?string $type): string
    {
        return match ($type) {
            AdministrativeAreaType::AREA => t('Area'),
            AdministrativeAreaType::CANTON => t('Canton'),
            AdministrativeAreaType::COUNTY => t('County'),
            AdministrativeAreaType::DEPARTMENT => t('Department'),
            AdministrativeAreaType::DISTRICT => t('District'),
            AdministrativeAreaType::DO_SI => t('Do Si'),
            AdministrativeAreaType::EMIRATE => t('Emirate'),
            AdministrativeAreaType::ISLAND => t('Island'),
            AdministrativeAreaType::PARISH => t('Parish'),
            AdministrativeAreaType::PREFECTURE => t('Prefecture'),
            AdministrativeAreaType::REGION => t('Region'),
            AdministrativeAreaType::STATE => t('State'),
            default => t('Province'),
        };
    }

    /** {@inheritdoc} */
    public function getHandle(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldLayout(): FieldLayout
    {
        $fieldLayout = $this->fields->getLayoutByType(Address::class);

        // Ensure it has at least one tab.
        // (The only reason this could possibly be null is if a module is removing all our own native fields
        // via EVENT_DEFINE_NATIVE_FIELDS.)
        $firstTab = $fieldLayout->getTabs()[0] ?? null;

        if (! $firstTab) {
            $firstTab = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => t('Content'),
            ]);
            $fieldLayout->setTabs([$firstTab]);
        }

        return $fieldLayout;
    }

    /**
     * Save the address field layout
     *
     * @param  bool  $runValidation  Whether the layout should be validated
     */
    public function saveFieldLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if ($runValidation && ! $layout->validate()) {
            Log::info('Field layout not saved due to validation error.', [__METHOD__]);

            return false;
        }

        $this->projectConfig->set(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, [
            $layout->uid => $layout->getConfig(),
        ], 'Save the address field layout');

        return true;
    }

    /**
     * Handle address field layout changes.
     */
    public function handleChangedAddressFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

        if (empty($data) || empty($config = reset($data))) {
            $this->fields->deleteLayoutsByType(Address::class);

            return;
        }

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        // Save the field layout
        $layout = FieldLayout::createFromConfig($config);
        $layout->id = $this->getFieldLayout()->id;
        $layout->type = Address::class;
        $layout->uid = key($data);
        $this->fields->saveLayout($layout);

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(Address::class);
    }
}
