<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRow
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TableRow extends ObjectType
{
    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;

        return $source[$fieldName];
    }

    /**
     * Take an array of columns and return fields prepared for GraphQL object definition.
     *
     * @param array $columns
     * @param bool $includeHandles Whether columns also should be present by their field handles.
     * @return array
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

            if ($includeHandles && !empty($columnDefinition['handle'])) {
                $contentFields[$columnDefinition['handle']] = $cellType;
            }
        }

        return $contentFields;
    }
}
