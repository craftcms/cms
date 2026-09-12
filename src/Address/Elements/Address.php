<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Elements;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Country\Country;
use CommerceGuys\Addressing\Subdivision\SubdivisionUpdater;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Concerns\LegacyConstants;
use CraftCms\Cms\Address\Conditions\AddressCondition;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Address\Validation\AddressRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Copy;
use CraftCms\Cms\Element\Concerns\NestedElement;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Shared\Concerns\HasNames;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Elements\User;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use Override;
use RuntimeException;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @property AddressRules $ruleset
 */
#[Ruleset(AddressRules::class)]
class Address extends Element implements AddressInterface, NestedElementInterface
{
    use HasNames;
    use LegacyConstants;
    use NestedElement;

    public const string GQL_TYPE_NAME = 'Address';

    /**
     * @var string Two-letter country code
     *
     * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
     */
    #[AllowedInSandbox]
    public string $countryCode;

    /**
     * @var string|null Administrative area
     */
    #[AllowedInSandbox]
    public ?string $administrativeArea = null;

    /**
     * @var string|null Locality
     */
    #[AllowedInSandbox]
    public ?string $locality = null;

    /**
     * @var string|null Dependent locality
     */
    #[AllowedInSandbox]
    public ?string $dependentLocality = null;

    /**
     * @var string|null Postal code
     */
    #[AllowedInSandbox]
    public ?string $postalCode = null;

    /**
     * @var string|null Sorting code
     */
    #[AllowedInSandbox]
    public ?string $sortingCode = null;

    /**
     * @var string|null First line of the address
     */
    #[AllowedInSandbox]
    public ?string $addressLine1 = null;

    /**
     * @var string|null Second line of the address
     */
    #[AllowedInSandbox]
    public ?string $addressLine2 = null;

    /**
     * @var string|null Third line of the address
     */
    #[AllowedInSandbox]
    public ?string $addressLine3 = null;

    /**
     * @var string|null Organization name
     */
    #[AllowedInSandbox]
    public ?string $organization = null;

    /**
     * @var string|null Organization tax ID
     */
    #[AllowedInSandbox]
    public ?string $organizationTaxId = null;

    /**
     * @var string|null Latitude
     */
    #[AllowedInSandbox]
    public ?string $latitude = null;

    /**
     * @var string|null Longitude
     */
    #[AllowedInSandbox]
    public ?string $longitude = null;

    /** @param array<string, mixed>|object $config */
    public function __construct(object|array $config = [])
    {
        parent::__construct($config);

        if (! isset($this->countryCode)) {
            $this->countryCode = Cms::config()->defaultCountryCode;
        }

        $this->normalizeNames();
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Address');
    }

    #[Override]
    public static function objectTemplateSuggestions(): array
    {
        return [
            ...parent::objectTemplateSuggestions(),
            'fullName' => t('Full Name'),
            'organization' => t('Organization'),
            'organizationTaxId' => t('Organization Tax ID'),
            'countryCode' => t('Country Code'),
            'administrativeArea' => t('Administrative Area'),
            'locality' => t('Locality'),
            'dependentLocality' => t('Dependent Locality'),
            'postalCode' => t('Postal Code'),
            'addressLine1' => t('Address Line 1'),
            'addressLine2' => t('Address Line 2'),
            'latitude' => t('Latitude'),
            'longitude' => t('Longitude'),
        ];
    }

    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('address');
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Addresses');
    }

    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('addresses');
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function hasStatuses(): bool
    {
        return false;
    }

    #[Override]
    public static function createCondition(): ElementConditionInterface
    {
        return new AddressCondition(self::class);
    }

    /** @return list<class-string<Copy>> */
    #[Override]
    protected static function defineActions(string $source): array
    {
        return [
            Copy::class,
        ];
    }

    /** @return array<string, array{label: string}> */
    #[Override]
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), [
            'country' => ['label' => t('Country')],
        ]);
    }

    #[Override]
    protected static function defineDefaultCardAttributes(): array
    {
        return [
            'address',
        ];
    }

    #[Override]
    protected function attributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'address' => Html::tag('div', app(Addresses::class)->formatAddress($this), [
                'class' => 'no-truncate',
            ]),
            'country' => $this->getCountry()->getName(),
            default => parent::attributeHtml($attribute),
        };
    }

    /** @return list<array{label: string, orderBy: string, attribute?: string, defaultDir?: 'asc'|'desc'}> */
    #[Override]
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => t('Label'),
                'orderBy' => 'title',
                'attribute' => 'title',
            ],
            [
                'label' => t('Country'),
                'orderBy' => 'countryCode',
                'attribute' => 'country',
            ],
            [
                'label' => t('Date Created'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('ID'),
                'orderBy' => 'id',
            ],
        ];
    }

    /**
     * @return AddressQuery The newly created [[AddressQuery]] instance.
     */
    #[Override]
    public static function find(): AddressQuery
    {
        return new AddressQuery;
    }

    #[Override]
    protected static function defineSearchableAttributes(): array
    {
        return self::addressAttributes();
    }

    /**
     * Returns all attributes
     *
     * @return string[]
     */
    public static function addressAttributes(): array
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

    /** @return list<string> */
    #[Override]
    public function safeAttributes(): array
    {
        return array_values(array_diff(parent::safeAttributes(), [
            'fieldId',
            'ownerId',
            'primaryOwnerId',
        ]));
    }

    #[Override]
    public function setAttributes($values): void
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

        parent::setAttributes($values);
    }

    #[Override]
    public function getAttributeLabel(string $attribute): string
    {
        if (AddressField::exists($attribute)) {
            /** @phpstan-var AddressField::* $attribute */
            return app(Addresses::class)->getFieldLabel($attribute, $this->countryCode);
        }

        return match ($attribute) {
            'title' => t('Label'),
            'organizationTaxId' => t('Organization Tax ID'),
            'fullName' => t('Full Name'),
            'firstName' => t('First Name'),
            'lastName' => t('Last Name'),
            'latitude' => t('Latitude'),
            'longitude' => t('Longitude'),
            default => parent::getAttributeLabel($attribute),
        };
    }

    /**
     * Returns whether the address belongs to the currently logged-in user.
     */
    #[AllowedInSandbox]
    public function getBelongsToCurrentUser(): bool
    {
        $owner = $this->getOwner();

        return $owner instanceof User && $owner->getIsCurrent();
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function safeActionMenuItems(): array
    {
        $items = parent::safeActionMenuItems();

        if (
            app(ElementRequest::class)->element === $this &&
            currentUser()->isAdmin() &&
            Cms::config()->allowAdminChanges &&
            ! empty($this->fieldId)
        ) {
            $items[] = ['type' => MenuItemType::HR];

            // Field settings
            $fieldEditId = sprintf('edit-field-%s', mt_rand());
            $items[] = [
                'id' => $fieldEditId,
                'icon' => 'gear',
                'label' => t('Field settings'),
            ];

            HtmlStack::jsWithVars(fn ($id, $params) => <<<JS
    (() => {
      $('#' + $id).on('activate', function() {
        new Craft.CpScreenSlideout(Craft.getCpUrl('settings/fields/edit'), {params: $params})
      });
    })();
    JS, [
                InputNamespace::namespaceId($fieldEditId),
                ['fieldId' => $this->fieldId],
            ]);
        }

        return $items;
    }

    #[AllowedInSandbox]
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Returns a [[Country]] object representing the address’ country.
     */
    #[AllowedInSandbox]
    public function getCountry(): Country
    {
        return app(Addresses::class)->getCountryRepository()->get($this->countryCode, app()->getLocale());
    }

    #[AllowedInSandbox]
    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    #[AllowedInSandbox]
    public function getLocality(): ?string
    {
        return $this->locality;
    }

    #[AllowedInSandbox]
    public function getDependentLocality(): ?string
    {
        return $this->dependentLocality;
    }

    #[AllowedInSandbox]
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    #[AllowedInSandbox]
    public function getSortingCode(): ?string
    {
        return $this->sortingCode;
    }

    #[AllowedInSandbox]
    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    #[AllowedInSandbox]
    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    #[AllowedInSandbox]
    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    #[AllowedInSandbox]
    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    #[AllowedInSandbox]
    public function getGivenName(): ?string
    {
        return $this->firstName;
    }

    #[AllowedInSandbox]
    public function getAdditionalName(): ?string
    {
        return null;
    }

    #[AllowedInSandbox]
    public function getFamilyName(): ?string
    {
        return $this->lastName;
    }

    #[AllowedInSandbox]
    public function getLocale(): string
    {
        return app()->getLocale();
    }

    #[Override]
    public function getGqlTypeName(): string
    {
        return self::GQL_TYPE_NAME;
    }

    /**
     * Returns whether the element's `title` attribute should be validated
     */
    #[Override]
    public function shouldValidateTitle(): bool
    {
        $titleField = $this->getFieldLayout()->getField('title');

        return $titleField->required && $titleField->showInForm($this);
    }

    #[Override]
    public function prepareForValidation(): void
    {
        $usedFields = array_unique([
            ...app(Addresses::class)->getUsedFields($this->countryCode),
            'fullName',
            'latLong',
            'organizationTaxId',
            'organization',
            'countryCode',
        ]);
        $nullFields = array_filter(
            array_diff(self::addressAttributes(), $usedFields),
            fn (string $attribute) => ! in_array($attribute, ['givenName', 'familyName', 'additionalName']),
        );

        foreach ($nullFields as $field) {
            $this->$field = null;
        }

        parent::prepareForValidation();
    }

    #[Override]
    public function getUiLabel(): string
    {
        return $this->title ?? '';
    }

    #[Override]
    public function getChipLabelHtml(): string
    {
        $html = (string) parent::getChipLabelHtml();

        if ($html !== '') {
            return $html;
        }

        return t('Untitled {type}', [
            'type' => self::lowerDisplayName(),
        ]);
    }

    #[Override]
    protected function cacheTags(): array
    {
        $tags = [];

        if (isset($this->fieldId)) {
            $tags[] = "field:$this->fieldId";
        }

        return $tags;
    }

    #[Override]
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
            if ($this->countryCode === 'AD' && isset($this->locality)) {
                $this->locality = SubdivisionUpdater::updateValue(
                    $this->countryCode,
                    $this->locality,
                );
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        if (! $isNew) {
            $model = AddressModel::find($this->id);

            if (! $model) {
                throw new RuntimeException("Invalid address ID: $this->id");
            }
        } else {
            $model = new AddressModel;
            $model->id = $this->id;
        }

        $this->prepareNamesForSave();

        $model->fieldId = $this->fieldId;
        $model->primaryOwnerId = $this->getPrimaryOwnerId();
        $model->countryCode = $this->countryCode;
        $model->administrativeArea = $this->administrativeArea;
        $model->locality = $this->locality;
        $model->dependentLocality = $this->dependentLocality;
        $model->postalCode = $this->postalCode;
        $model->sortingCode = $this->sortingCode;
        $model->addressLine1 = $this->addressLine1;
        $model->addressLine2 = $this->addressLine2;
        $model->addressLine3 = $this->addressLine3;
        $model->organization = $this->organization;
        $model->organizationTaxId = $this->organizationTaxId;
        $model->fullName = $this->fullName;
        $model->firstName = $this->firstName;
        $model->lastName = $this->lastName;
        $model->latitude = $this->latitude;
        $model->longitude = $this->longitude;

        // Capture the dirty attributes from the record
        $dirtyAttributes = array_keys($model->getDirty());
        $model->save();
        $this->setDirtyAttributes($dirtyAttributes);

        $this->saveOwnership($isNew, Table::ADDRESSES);

        parent::afterSave($isNew);
    }

    #[Override]
    public function getFieldLayout(): FieldLayout
    {
        return app(Addresses::class)->getFieldLayout();
    }

    #[Override]
    public static function isImportable(): bool
    {
        // address elements never exist on their own; they're always either owned by something or is the content of Addresses field;
        // addresses can be imported via an importable element that has a field layout that contains Addresses field,
        // or via User addresses
        return false;
    }
}
