<?php

declare(strict_types=1);

namespace CraftCms\Cms\Addresses\Events;

final class DefineAddressSubdivisions
{
    public function __construct(
        /**
         * @var array The field's parents; always in order of: countryCode, administrativeArea, locality
         */
        public array $parents,
        /** @var string[] $subdivisions The subdivisions */
        public array $subdivisions,
    ) {}
}
