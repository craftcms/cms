<?php

namespace craft\elements;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use craft\base\ElementEventConstants;
use CraftCms\Cms\Address\Addresses;
use Deprecated;

/**
 * Address element class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Elements\Address} instead.
 */
class Address extends \CraftCms\Cms\Address\Elements\Address
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
