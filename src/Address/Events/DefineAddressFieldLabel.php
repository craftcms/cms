<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Events;

class DefineAddressFieldLabel
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
