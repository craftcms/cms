<?php

namespace CraftCms\Cms\Addresses\Events;

/** @since 6.0.0 */
final class DefineAddressFieldLabel
{
    public function __construct(
        public string $countryCode,
        /**
         * @var string $field The field to define a label for (one of the [[AddressField]] constants)
         *
         * @see AddressField
         */
        public string $field,
        public string $label,
    ) {}
}
