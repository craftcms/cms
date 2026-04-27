<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Gql\Interfaces\Elements\Address as AddressInterface;

class Address extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            AddressInterface::getType(),
        ];

        parent::__construct($config);
    }
}
