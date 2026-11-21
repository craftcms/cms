<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Element\Eloquent\EloquentElement as EloquentElementBase;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

/**
 * @since 6.0.0
 */
class EloquentElement extends Element
{
    /**
     * Resolves custom fields by their own values, as the element’s model attributes shadow custom fields with the
     * same handles.
     */
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;

        if (
            $source instanceof EloquentElementBase &&
            $source->isModelAttribute($fieldName) &&
            $source->getFieldLayout()->getFieldByHandle($fieldName) !== null
        ) {
            $value = $source->getFieldValue($fieldName);

            return $value instanceof ElementQueryInterface ? $value->all() : $value;
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
