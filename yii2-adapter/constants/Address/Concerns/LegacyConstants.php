<?php

declare(strict_types=1);
namespace CraftCms\Cms\Address\Concerns;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use craft\base\ElementEventConstants;
use CraftCms\Cms\Address\Addresses;
use Deprecated;

/**
 * @internal
 * @deprecated 6.0.0
 */
trait LegacyConstants
{
    use ElementEventConstants;

    /**
     * Returns an address attribute label.
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Addresses::getFieldLabel()]] should be used instead.')]
    public static function addressAttributeLabel(string $attribute, string $countryCode): ?string
    {
        if (!AddressField::exists($attribute)) {
            return null;
        }

        /** @phpstan-var AddressField::* $attribute */
        return app(Addresses::class)->getFieldLabel($attribute, $countryCode);
    }
}
