<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CommerceGuys\Addressing\Country\CountryRepository getCountryRepository()
 * @method static \CraftCms\Cms\Address\Repositories\SubdivisionRepository getSubdivisionRepository()
 * @method static \CommerceGuys\Addressing\AddressFormat\AddressFormatRepository getAddressFormatRepository()
 * @method static array defineAddressSubdivisions(array $parents, array $options = [])
 * @method static array getCountryList(string|null $locale = null)
 * @method static string[] getUsedFields(string $countryCode)
 * @method static string[] getUsedSubdivisionFields(string $countryCode)
 * @method static string getFieldLabel(string $field, string $countryCode)
 * @method static array getFormFieldDefinitions(\CraftCms\Cms\Address\Elements\Address $address, bool|null $belongsToCurrentUser = null)
 * @method static string formatAddress(\CraftCms\Cms\Address\Elements\Address $address, array $options = [], \CommerceGuys\Addressing\Formatter\FormatterInterface|null $formatter = null)
 * @method static string getLocalityTypeLabel(string|null $type)
 * @method static string getDependentLocalityTypeLabel(string|null $type)
 * @method static string getPostalCodeTypeLabel(string|null $type)
 * @method static string getAdministrativeAreaTypeLabel(string|null $type)
 * @method static string|null getHandle()
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout getFieldLayout()
 * @method static bool saveFieldLayout(\CraftCms\Cms\FieldLayout\FieldLayout $layout, bool $runValidation = true)
 * @method static void handleChangedAddressFieldLayout(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 *
 * @see \CraftCms\Cms\Address\Addresses
 */
class Addresses extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Address\Addresses::class;
    }
}
