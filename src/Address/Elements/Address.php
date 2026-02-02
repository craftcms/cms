<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Elements;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Country\Country;
use CommerceGuys\Addressing\Subdivision\SubdivisionUpdater;
use Craft;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\elements\actions\Copy;
use craft\elements\conditions\addresses\AddressCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\models\FieldLayout;
use craft\web\twig\AllowedInSandbox;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Address\Validation\AddressRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Shared\Concerns\HasNames;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use Deprecated;
use Override;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

#[Ruleset(AddressRules::class)]
final class Address extends Element implements AddressInterface, NestedElementInterface
{
    use HasNames;
    use NestedElementTrait;

    /**
     * @since 5.0.0
     */
    public const string GQL_TYPE_NAME = 'Address';

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Address');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('address');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Addresses');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('addresses');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(AddressCondition::class, [self::class]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineActions(string $source): array
    {
        return [
            Copy::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), [
            'country' => ['label' => t('Country')],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function attributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'country' => $this->getCountry()->getName(),
            default => parent::attributeHtml($attribute),
        };
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     *
     * @return AddressQuery The newly created [[AddressQuery]] instance.
     */
    #[Override]
    public static function find(): AddressQuery
    {
        return new AddressQuery;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * Returns an address attribute label.
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Addresses::getFieldLabel()]] should be used instead.')]
    public static function addressAttributeLabel(string $attribute, string $countryCode): ?string
    {
        if (! AddressField::exists($attribute)) {
            return null;
        }

        /** @phpstan-var AddressField::* $attribute */
        return app(Addresses::class)->getFieldLabel($attribute, $countryCode);
    }

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
     *
     * @since 5.0.0
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function init(): void
    {
        parent::init();

        if (! isset($this->countryCode)) {
            $this->countryCode = Cms::config()->defaultCountryCode;
        }

        $this->normalizeNames();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
     */
    #[Override]
    public function getAttributeLabel($attribute): string
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
     *
     * @since 4.5.13
     */
    public function getBelongsToCurrentUser(): bool
    {
        $owner = $this->getOwner();

        return $owner instanceof User && $owner->getIsCurrent();
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Returns a [[Country]] object representing the address’ country.
     *
     * @since 5.3.0
     */
    #[AllowedInSandbox]
    public function getCountry(): Country
    {
        return app(Addresses::class)->getCountryRepository()->get($this->countryCode, app()->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getLocality(): ?string
    {
        return $this->locality;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getDependentLocality(): ?string
    {
        return $this->dependentLocality;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getSortingCode(): ?string
    {
        return $this->sortingCode;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getGivenName(): ?string
    {
        return $this->firstName;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getAdditionalName(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getFamilyName(): ?string
    {
        return $this->lastName;
    }

    /**
     * {@inheritdoc}
     */
    #[AllowedInSandbox]
    public function getLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.3.0
     */
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function beforeValidate(): bool
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

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getUiLabel(): string
    {
        return $this->title ?? '';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function cacheTags(): array
    {
        $tags = [];

        if (isset($this->fieldId)) {
            $tags[] = "field:$this->fieldId";
        }

        return $tags;
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        if (! $isNew) {
            $model = AddressModel::find($this->id);

            if (! $model) {
                throw new InvalidConfigException("Invalid address ID: $this->id");
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFieldLayout(): FieldLayout
    {
        return app(Addresses::class)->getFieldLayout();
    }
}
