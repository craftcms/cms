<?php

namespace CraftCms\Cms\Addresses;

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
use craft\elements\Address;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use CraftCms\Cms\Addresses\Events\DefineAddressCountries;
use CraftCms\Cms\Addresses\Events\DefineAddressFieldLabel;
use CraftCms\Cms\Addresses\Events\DefineAddressSubdivisions;
use CraftCms\Cms\Addresses\Events\DefineAddressUsedFields;
use CraftCms\Cms\Addresses\Events\DefineAddressUsedSubdivisionFields;
use CraftCms\Cms\Addresses\Repositories\SubdivisionRepository;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Event;

/** @since 6.0.0 */
#[Singleton]
final class Addresses implements FieldLayoutProviderInterface
{
    private FormatterInterface $formatter;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly CountryRepository $countryRepository,
        private readonly SubdivisionRepository $subdivisionRepository,
        private readonly AddressFormatRepository $addressFormatRepository,
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
        if (Event::hasListeners(DefineAddressSubdivisions::class)) {
            Event::dispatch($event = new DefineAddressSubdivisions($parents, $options));

            return $event->subdivisions;
        }

        return $options;
    }

    /**
     * Returns a list of countries to be used as options for selection.
     */
    public function getCountryList(?string $locale = null): array
    {
        $locale ??= Craft::$app->language;
        $countries = $this->getCountryRepository()->getList($locale);

        if (Event::hasListeners(DefineAddressCountries::class)) {
            Event::dispatch($event = new DefineAddressCountries($locale, $countries));

            return $event->countries;
        }

        return $countries;
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
        $fields = $this->getAddressFormatRepository()->get($countryCode)->getUsedFields();

        if (Event::hasListeners(DefineAddressUsedFields::class)) {
            Event::dispatch($event = new DefineAddressUsedFields($countryCode, $fields));

            return $event->fields;
        }

        return $fields;
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
        $fields = $this->getAddressFormatRepository()->get($countryCode)->getUsedSubdivisionFields();

        if (Event::hasListeners(DefineAddressUsedSubdivisionFields::class)) {
            Event::dispatch($event = new DefineAddressUsedSubdivisionFields($countryCode, $fields));

            return $event->fields;
        }

        return $fields;
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
            AddressField::ADMINISTRATIVE_AREA => $this->getAdministrativeAreaTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getAdministrativeAreaType()),
            AddressField::LOCALITY => $this->getLocalityTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getLocalityType()),
            AddressField::DEPENDENT_LOCALITY => $this->getDependentLocalityTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getDependentLocalityType()),
            AddressField::POSTAL_CODE => $this->getPostalCodeTypeLabel($this->getAddressFormatRepository()->get($countryCode)->getPostalCodeType()),
            AddressField::SORTING_CODE => Craft::t('app', 'Sorting Code'),
            AddressField::ADDRESS_LINE1 => Craft::t('app', 'Address Line 1'),
            AddressField::ADDRESS_LINE2 => Craft::t('app', 'Address Line 2'),
            AddressField::ADDRESS_LINE3 => Craft::t('app', 'Address Line 3'),
            AddressField::ORGANIZATION => Craft::t('app', 'Organization'),
            AddressField::GIVEN_NAME => Craft::t('app', 'First Name'),
            AddressField::ADDITIONAL_NAME => 'Additional Name', // Unused in Craft
            AddressField::FAMILY_NAME => Craft::t('app', 'Last Name'),
        };

        if (Event::hasListeners(DefineAddressFieldLabel::class)) {
            Event::dispatch($event = new DefineAddressFieldLabel($countryCode, $field, $label));

            return $event->label;
        }

        return $label;
    }

    /**
     * Formats the address model into the correct sequence and format in HTML.
     */
    public function formatAddress(Address $address, array $options = [], ?FormatterInterface $formatter = null): string
    {
        $options['locale'] ??= Craft::$app->language;
        $formatter ??= $this->formatter;

        return $formatter->format($address, $options);
    }

    public function getLocalityTypeLabel(?string $type): string
    {
        return match ($type) {
            LocalityType::DISTRICT => Craft::t('app', 'District'),
            LocalityType::POST_TOWN => Craft::t('app', 'Post Town'),
            LocalityType::SUBURB => Craft::t('app', 'Suburb'),
            LocalityType::TOWN_CITY => Craft::t('app', 'City/Town'),
            default => Craft::t('app', 'City'),
        };
    }

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

    public function getPostalCodeTypeLabel(?string $type): string
    {
        return match ($type) {
            PostalCodeType::EIR => Craft::t('app', 'Eircode'),
            PostalCodeType::PIN => Craft::t('app', 'Pin'),
            PostalCodeType::ZIP => Craft::t('app', 'Zip Code'),
            default => Craft::t('app', 'Postal Code'),
        };
    }

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
            AdministrativeAreaType::PARISH => Craft::t('app', 'Parish'),
            AdministrativeAreaType::PREFECTURE => Craft::t('app', 'Prefecture'),
            AdministrativeAreaType::REGION => Craft::t('app', 'Region'),
            AdministrativeAreaType::STATE => Craft::t('app', 'State'),
            default => Craft::t('app', 'Province'),
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
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(Address::class);

        // Ensure it has at least one tab.
        // (The only reason this could possibly be null is if a module is removing all our own native fields
        // via EVENT_DEFINE_NATIVE_FIELDS.)
        $firstTab = $fieldLayout->getTabs()[0] ?? null;
        if (! $firstTab) {
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
     * @param  bool  $runValidation  Whether the layout should be validated
     */
    public function saveFieldLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if ($runValidation && ! $layout->validate()) {
            Craft::info('Field layout not saved due to validation error.', __METHOD__);

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

        $fieldsService = Craft::$app->getFields();

        if (empty($data) || empty($config = reset($data))) {
            $fieldsService->deleteLayoutsByType(Address::class);

            return;
        }

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        // Save the field layout
        $layout = FieldLayout::createFromConfig($config);
        $layout->id = $this->getFieldLayout()->id;
        $layout->type = Address::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(Address::class);
    }
}
