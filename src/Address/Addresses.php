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
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Events\AddressCountriesResolving;
use CraftCms\Cms\Address\Events\AddressFieldLabelResolving;
use CraftCms\Cms\Address\Events\AddressSubdivisionsResolving;
use CraftCms\Cms\Address\Events\AddressUsedFieldsResolving;
use CraftCms\Cms\Address\Events\AddressUsedSubdivisionFieldsResolving;
use CraftCms\Cms\Address\Repositories\SubdivisionRepository;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rules\RequiredIf;

use function CraftCms\Cms\t;

/**
 * @phpstan-type AddressFormField array{
 *     name: string,
 *     label: string,
 *     type: 'select'|'text',
 *     value: string|null,
 *     visible: bool,
 *     required: bool,
 *     autocomplete: string|null,
 *     status: array{0: string, 1: string}|null,
 *     errors: list<string>,
 *     options?: array<string, string>,
 *     spinner?: bool,
 *     width?: int,
 * }
 * @phpstan-type AddressVisibleFields array<string, int|true>
 * @phpstan-type AddressSubdivisionParents array{
 *     locality: list<string|null>,
 *     dependentLocality: list<string|null>,
 * }
 */
#[Singleton]
readonly class Addresses implements FieldLayoutProviderInterface
{
    private FormatterInterface $formatter;

    public function __construct(
        private ProjectConfig $projectConfig,
        private CountryRepository $countryRepository,
        private SubdivisionRepository $subdivisionRepository,
        private AddressFormatRepository $addressFormatRepository,
        private Fields $fields,
        private ElementCaches $elementCaches,
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
     *
     * @param  list<string>  $parents
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    public function defineAddressSubdivisions(array $parents, array $options = []): array
    {
        event($event = new AddressSubdivisionsResolving($parents, $options));

        return $event->subdivisions;
    }

    /**
     * Returns a list of countries to be used as options for selection.
     *
     * @return array<string, string>
     */
    public function getCountryList(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $countries = $this->countryRepository->getList($locale);

        event($event = new AddressCountriesResolving($locale, $countries));

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

        event($event = new AddressUsedFieldsResolving($countryCode, $fields));

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

        event($event = new AddressUsedSubdivisionFieldsResolving($countryCode, $fields));

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

        event($event = new AddressFieldLabelResolving($countryCode, $field, $label));

        return $event->label;
    }

    /** @return list<AddressFormField> */
    public function getFormFieldDefinitions(Address $address, ?bool $belongsToCurrentUser = null): array
    {
        $requiredFields = [];
        $scenario = $address->ruleset->getScenario();
        $address->ruleset->useScenario(ElementRules::SCENARIO_LIVE);

        foreach ($address->ruleset->rules() as $attribute => $rules) {
            foreach (Arr::wrap($rules) as $rule) {
                if ($this->isRequiredRule($rule)) {
                    $requiredFields[$attribute] = true;

                    break;
                }
            }
        }

        $address->ruleset->useScenario($scenario);
        $belongsToCurrentUser ??= $address->getBelongsToCurrentUser();
        $visibleFields = array_flip(array_merge(
            $this->getUsedFields($address->countryCode),
            $this->getUsedSubdivisionFields($address->countryCode),
        )) + $requiredFields;
        $parents = $this->subdivisionParents($address, $visibleFields);

        return [
            $this->textFieldDefinition($address, 'addressLine1', true, isset($requiredFields['addressLine1']), $belongsToCurrentUser ? 'address-line1' : 'off'),
            $this->textFieldDefinition($address, 'addressLine2', true, isset($requiredFields['addressLine2']), $belongsToCurrentUser ? 'address-line2' : 'off'),
            $this->textFieldDefinition($address, 'addressLine3', true, isset($requiredFields['addressLine3']), $belongsToCurrentUser ? 'address-line3' : 'off'),
            $this->subdivisionFieldDefinition(
                $address,
                'administrativeArea',
                $belongsToCurrentUser ? 'address-level1' : 'off',
                isset($visibleFields['administrativeArea']),
                isset($requiredFields['administrativeArea']),
                [$address->countryCode],
                true,
            ),
            $this->subdivisionFieldDefinition(
                $address,
                'locality',
                $belongsToCurrentUser ? 'address-level2' : 'off',
                isset($visibleFields['locality']),
                isset($requiredFields['locality']),
                $parents['locality'],
                true,
            ),
            $this->subdivisionFieldDefinition(
                $address,
                'dependentLocality',
                $belongsToCurrentUser ? 'address-level3' : 'off',
                isset($visibleFields['dependentLocality']),
                isset($requiredFields['dependentLocality']),
                $parents['dependentLocality'],
                false,
            ),
            $this->textFieldDefinition(
                $address,
                'postalCode',
                isset($visibleFields['postalCode']),
                isset($requiredFields['postalCode']),
                $belongsToCurrentUser ? 'postal-code' : 'off',
                50,
            ),
            $this->textFieldDefinition(
                $address,
                'sortingCode',
                isset($visibleFields['sortingCode']),
                isset($requiredFields['sortingCode']),
                width: 50,
            ),
        ];
    }

    /** @return AddressFormField */
    private function textFieldDefinition(
        Address $address,
        string $name,
        bool $visible,
        bool $required,
        ?string $autocomplete = null,
        ?int $width = null,
    ): array {
        $status = $address->getAttributeStatus($name);
        $definition = [
            'name' => $name,
            'label' => $address->getAttributeLabel($name),
            'type' => 'text',
            'value' => $address->$name,
            'visible' => $visible,
            'required' => $required,
            'autocomplete' => $autocomplete,
            'status' => $status ? [Str::toString($status[0]), $status[1]] : null,
            'errors' => $address->errors()->get($name),
        ];

        if ($width !== null) {
            $definition['width'] = $width;
        }

        return $definition;
    }

    /**
     * @param  list<string|null>  $parents
     * @return AddressFormField
     */
    private function subdivisionFieldDefinition(
        Address $address,
        string $name,
        string $autocomplete,
        bool $visible,
        bool $required,
        array $parents,
        bool $spinner,
    ): array {
        $definition = $this->textFieldDefinition($address, $name, $visible, $required, $autocomplete);
        $options = $this->subdivisionRepository->getList($parents, app()->getLocale());

        if ($options === []) {
            return $definition;
        }

        $value = $address->$name;
        if ($value && ! isset($options[$value])) {
            $options[$value] = $value;
        }

        return [
            ...$definition,
            'type' => 'select',
            'options' => $options,
            'spinner' => $spinner,
        ];
    }

    /**
     * @param  AddressVisibleFields  $visibleFields
     * @return AddressSubdivisionParents
     */
    private function subdivisionParents(Address $address, array $visibleFields): array
    {
        $repository = new BaseSubdivisionRepository;
        $localityParents = [$address->countryCode];
        $administrativeAreas = $repository->getList([$address->countryCode]);

        if (isset($visibleFields['administrativeArea']) || $administrativeAreas === []) {
            $localityParents[] = $address->administrativeArea;
        }

        $dependentLocalityParents = $localityParents;
        $localities = $repository->getList($localityParents);
        if (isset($visibleFields['locality']) || $localities === []) {
            $dependentLocalityParents[] = $address->locality;
        }

        return ['locality' => $localityParents, 'dependentLocality' => $dependentLocalityParents];
    }

    private function isRequiredRule(mixed $rule): bool
    {
        if ($rule === 'required') {
            return true;
        }

        if ($rule instanceof RequiredIf) {
            return (string) $rule === 'required';
        }

        if (! $rule instanceof ConditionalRules) {
            return false;
        }

        $conditionalRules = $rule->passes() ? $rule->rules() : $rule->defaultRules();

        foreach (Arr::wrap($conditionalRules) as $conditionalRule) {
            if ($this->isRequiredRule($conditionalRule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formats the address model into the correct sequence and format in HTML.
     *
     * @param  array{locale?: string, html?: bool, html_tag?: string, html_attributes?: array<string, string>}  $options
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

    public function getHandle(): ?string
    {
        return null;
    }

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
                'name' => FieldLayout::defaultTabName(),
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
        $this->elementCaches->invalidateForElementType(Address::class);
    }
}
