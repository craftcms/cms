<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\FormatterInterface;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Repositories\SubdivisionRepository;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static CountryRepository getCountryRepository()
 * @method static SubdivisionRepository getSubdivisionRepository()
 * @method static AddressFormatRepository getAddressFormatRepository()
 * @method static array defineAddressSubdivisions(array $parents, array $options = [])
 * @method static array getCountryList(string|null $locale = null)
 * @method static string[] getUsedFields(string $countryCode)
 * @method static string[] getUsedSubdivisionFields(string $countryCode)
 * @method static string getFieldLabel(string $field, string $countryCode)
 * @method static string formatAddress(Address $address, array $options = [], FormatterInterface|null $formatter = null)
 * @method static string getLocalityTypeLabel(string|null $type)
 * @method static string getDependentLocalityTypeLabel(string|null $type)
 * @method static string getPostalCodeTypeLabel(string|null $type)
 * @method static string getAdministrativeAreaTypeLabel(string|null $type)
 * @method static string|null getHandle()
 * @method static FieldLayout getFieldLayout()
 * @method static bool saveFieldLayout(FieldLayout $layout, bool $runValidation = true)
 * @method static void handleChangedAddressFieldLayout(ConfigEvent $event)
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
