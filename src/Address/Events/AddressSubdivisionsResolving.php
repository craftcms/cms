<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Events;

class AddressSubdivisionsResolving
{
    public function __construct(
        /**
         * @var list<string> The field's parents; always in order of: countryCode, administrativeArea, locality
         */
        public array $parents,
        /** @var array<string, string> $subdivisions The subdivisions */
        public array $subdivisions,
    ) {}
}
