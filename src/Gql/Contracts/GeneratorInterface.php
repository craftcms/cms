<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

use CraftCms\Cms\Gql\Types\ObjectType;

interface GeneratorInterface
{
    /**
     * @param  mixed  $context  Context for generated types
     * @return ObjectType[]
     */
    public static function generateTypes(mixed $context = null): array;
}
