<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Events;

class AddressCountriesResolving
{
    public function __construct(
        public string $locale,
        /** @var array<string, string> List of countries keyed by their country code. */
        public array $countries,
    ) {}
}
