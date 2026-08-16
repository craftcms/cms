<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Types\QueryArgument;
use GraphQL\Language\AST\FloatValueNode;

it('rejects invalid query condition values', function () {
    QueryArgument::getType()->parseLiteral(new FloatValueNode(['value' => '1.5']));
})->throws(GqlException::class, 'QueryArgument must be either a string, an integer, or a boolean value.');
