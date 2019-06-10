<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class TableRow
 */
class TableRow extends BaseType
{
    /**
     * @inheritdoc
     */
    public function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        $fieldName = $resolveInfo->fieldName;

        return $source[$fieldName];
    }
}
