<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Events;

abstract class DefineAddressFields
{
    public function __construct(
        public string $countryCode,
        /** @var string[] $fields The fields available for the country */
        public array $fields,
    ) {}
}
