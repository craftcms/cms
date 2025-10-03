<?php

namespace CraftCms\Cms\Addresses\Events;

/** @since 6.0.0 */
final class DefineAddressCountries
{
    public function __construct(
        public string $locale,
        /** @var array list of countries keyed by their country code. */
        public array $countries,
    ) {}
}
