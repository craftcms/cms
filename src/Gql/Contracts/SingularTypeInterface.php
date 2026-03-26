<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Contracts;

use GraphQL\Type\Definition\Type;

interface SingularTypeInterface
{
    public static function getName(): string;

    public static function getType(): Type;
}
