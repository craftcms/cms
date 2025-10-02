<?php

namespace CraftCms\Cms\Addresses\Events;

/** @since 6.0.0 */
abstract class DefineAddressFields
{
    public function __construct(
        public string $countryCode,
        /** @var string[] $fields The fields available for the country */
        public array $fields,
    ) {}
}
