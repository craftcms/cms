<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;

class TableRow extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;

        return $source[$fieldName];
    }

    /**
     * @param  bool  $includeHandles  Whether columns also should be present by their field handles.
     */
    public static function prepareRowFieldDefinition(array $columns, bool $includeHandles = true): array
    {
        $contentFields = [];

        foreach ($columns as $columnKey => $columnDefinition) {
            $cellType = match ($columnDefinition['type']) {
                'date', 'time' => DateTime::getType(),
                'number' => Number::getType(),
                'lightswitch' => Type::boolean(),
                default => Type::string(),
            };

            $contentFields[$columnKey] = $cellType;

            if ($includeHandles && ! empty($columnDefinition['handle'])) {
                $contentFields[$columnDefinition['handle']] = $cellType;
            }
        }

        return $contentFields;
    }
}
