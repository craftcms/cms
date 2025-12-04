<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\FormatterInterface;
use Craft;
use craft\base\FieldLayoutProviderInterface;
use craft\elements\Address;
use craft\events\DefineAddressCountriesEvent;
use craft\events\DefineAddressFieldLabelEvent;
use craft\events\DefineAddressFieldsEvent;
use craft\events\DefineAddressSubdivisionsEvent;
use craft\models\FieldLayout;
use CraftCms\Cms\Address\Events\DefineAddressCountries;
use CraftCms\Cms\Address\Events\DefineAddressFieldLabel;
use CraftCms\Cms\Address\Events\DefineAddressSubdivisions;
use CraftCms\Cms\Address\Events\DefineAddressUsedFields;
use CraftCms\Cms\Address\Events\DefineAddressUsedSubdivisionFields;
use CraftCms\Cms\Address\Repositories\SubdivisionRepository;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * Addresses service.
 * An instance of the Addresses service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAddresses()|`Craft::$app->getAddresses()`]].
 *
 * @property-read AddressFormatRepository $addressFormatRepository
 * @property-read CountryRepository $countryRepository
 * @property-read SubdivisionRepository $subdivisionRepository
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Addresses} instead.
 */
class Addresses extends Component implements FieldLayoutProviderInterface
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
     * @event DefineAddressSubdivisionsEvent The event that is triggered when defining subdivisions options for an address field
     * for a given country code, and optionally administrativeArea and locality.
     * @see defineAddressSubdivisions()
     * @since 4.5.0
     */
    public const EVENT_DEFINE_ADDRESS_SUBDIVISIONS = 'defineAddressSubdivisions';

    /**
     * @event DefineAddressCountriesEvent The event that is triggered when defining country options for an address.
     *
     * This event is primarily used to modify the list of countries that are available for selection. You can also use
     * the event to add additional countries to the list, however, this will require you to use dependency injection to override the
     * `Addresses::getCountryRepository()` method and provide your own `CountryRepository` instance.
     *
     * @see getCountryList()
     * @since 5.5.0
     */
    public const EVENT_DEFINE_ADDRESS_COUNTRIES = 'defineAddressCountries';

    /**
     * @var FormatterInterface|null The default address formatter used by [[formatAddress()]]
     * @since 4.5.0
     */
    public ?FormatterInterface $formatter = null;

    /**
     * @return CountryRepository
     */
    public function getCountryRepository(): CountryRepository
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getCountryRepository();
    }

    /**
     * @return SubdivisionRepository
     */
    public function getSubdivisionRepository(): SubdivisionRepository
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getSubdivisionRepository();
    }

    /**
     * @return AddressFormatRepository
     */
    public function getAddressFormatRepository(): AddressFormatRepository
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getAddressFormatRepository();
    }

    /**
     * Returns subdivisions for a field based on its parents.
     *
     * @param array $parents
     * @param array $options
     * @return array
     * @since 4.5.0
     */
    public function defineAddressSubdivisions(array $parents, array $options = []): array
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->defineAddressSubdivisions($parents, $options);
    }

    /**
     * Returns a list of countries to be used as options for selection.
     *
     * @param string|null $locale
     * @return array
     * @since 5.5.0
     */
    public function getCountryList(?string $locale = null): array
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getCountryList($locale);
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
        return app(\CraftCms\Cms\Address\Addresses::class)->getUsedFields($countryCode);
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
        return app(\CraftCms\Cms\Address\Addresses::class)->getUsedSubdivisionFields($countryCode);
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
        return app(\CraftCms\Cms\Address\Addresses::class)->getFieldLabel($field, $countryCode);
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
        return app(\CraftCms\Cms\Address\Addresses::class)->formatAddress($address, $options, $formatter);
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getLocalityTypeLabel(?string $type): string
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getlocalityTypeLabel($type);
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getDependentLocalityTypeLabel(?string $type): string
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getDependentLocalityTypeLabel($type);
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getPostalCodeTypeLabel(?string $type): string
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getPostalCodeTypeLabel($type);
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function getAdministrativeAreaTypeLabel(?string $type): string
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getAdministrativeAreaTypeLabel($type);
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): ?string
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getHandle();
    }

    /**
     * @inheritdoc
     * @since 5.0.0
     */
    public function getFieldLayout(): FieldLayout
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->getFieldLayout();
    }

    /**
     * Save the address field layout
     *
     * @param FieldLayout $layout
     * @param bool $runValidation Whether the layout should be validated
     * @return bool
     */
    public function saveFieldLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        return app(\CraftCms\Cms\Address\Addresses::class)->saveFieldLayout($layout, $runValidation);
    }

    /**
     * Handle address field layout changes.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedAddressFieldLayout(ConfigEvent $event): void
    {
        app(\CraftCms\Cms\Address\Addresses::class)->handleChangedAddressFieldLayout($event);
    }

    public static function registerEvents(): void
    {
        Event::listen(DefineAddressSubdivisions::class, function(DefineAddressSubdivisions $event) {
            if (!Craft::$app->getAddresses()->hasEventHandlers(self::EVENT_DEFINE_ADDRESS_SUBDIVISIONS)) {
                return;
            }
            Craft::$app->getAddresses()->trigger(self::EVENT_DEFINE_ADDRESS_SUBDIVISIONS, $yiiEvent = new DefineAddressSubdivisionsEvent([
                'parents' => $event->parents,
                'subdivisions' => $event->subdivisions,
            ]));
            $event->subdivisions = $yiiEvent->subdivisions;
        });

        Event::listen(DefineAddressCountries::class, function(DefineAddressCountries $event) {
            if (!Craft::$app->getAddresses()->hasEventHandlers(self::EVENT_DEFINE_ADDRESS_COUNTRIES)) {
                return;
            }
            Craft::$app->getAddresses()->trigger(self::EVENT_DEFINE_ADDRESS_COUNTRIES, $yiiEvent = new DefineAddressCountriesEvent([
                'locale' => $event->locale,
                'countries' => $event->countries,
            ]));
            $event->countries = $yiiEvent->countries;
        });

        Event::listen(DefineAddressUsedFields::class, function(DefineAddressUsedFields $event) {
            if (!Craft::$app->getAddresses()->hasEventHandlers(self::EVENT_DEFINE_USED_FIELDS)) {
                return;
            }
            Craft::$app->getAddresses()->trigger(self::EVENT_DEFINE_USED_FIELDS, $yiiEvent = new DefineAddressFieldsEvent([
                'countryCode' => $event->countryCode,
                'fields' => $event->fields,
            ]));
            $event->fields = $yiiEvent->fields;
        });

        Event::listen(DefineAddressUsedSubdivisionFields::class, function(DefineAddressUsedSubdivisionFields $event) {
            if (!Craft::$app->getAddresses()->hasEventHandlers(self::EVENT_DEFINE_USED_SUBDIVISION_FIELDS)) {
                return;
            }
            Craft::$app->getAddresses()->trigger(self::EVENT_DEFINE_USED_SUBDIVISION_FIELDS, $yiiEvent = new DefineAddressFieldsEvent([
                'countryCode' => $event->countryCode,
                'fields' => $event->fields,
            ]));
            $event->fields = $yiiEvent->fields;
        });

        Event::listen(DefineAddressFieldLabel::class, function(DefineAddressFieldLabel $event) {
            if (!Craft::$app->getAddresses()->hasEventHandlers(self::EVENT_DEFINE_FIELD_LABEL)) {
                return;
            }
            Craft::$app->getAddresses()->trigger(self::EVENT_DEFINE_FIELD_LABEL, $yiiEvent = new DefineAddressFieldLabelEvent([
                'countryCode' => $event->countryCode,
                'field' => $event->field,
                'label' => $event->label,
            ]));
            $event->label = $yiiEvent->label;
        });
    }
}
