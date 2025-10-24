<?php

declare(strict_types=1);

namespace CraftCms\Cms\Addresses\Events;

abstract class DefineAddressFields
{
    public function __construct(
        public string $countryCode,
        /** @var string[] $fields The fields available for the country */
        public array $fields,
    ) {}
}
