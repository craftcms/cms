<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Events;

class DefineAddressCountries
{
    public function __construct(
        public string $locale,
        /** @var array list of countries keyed by their country code. */
        public array $countries,
    ) {}
}
