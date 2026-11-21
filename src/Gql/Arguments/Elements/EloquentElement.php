<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Element\Eloquent\EloquentElement as EloquentElementType;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Argument;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
class EloquentElement extends ElementArguments
{
    /**
     * Returns the arguments for querying an Eloquent element type: the element arguments, and one per custom field in its layout.
     *
     * @param  class-string<EloquentElementType<covariant Model>>  $elementType
     * @return array<string, ArgumentConfig>
     */
    public static function forElementType(string $elementType): array
    {
        // Custom field arguments can’t shadow the element’s own arguments.
        return array_merge(Gql::getFieldLayoutArguments($elementType::resolveFieldLayout()), static::getArguments());
    }
}
