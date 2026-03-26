<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

interface SingleGeneratorInterface
{
    /**
     * @param  mixed  $context  Context for generated types
     */
    public static function generateType(mixed $context): mixed;
}
