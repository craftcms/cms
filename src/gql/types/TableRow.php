<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\TypeManager;
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
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        $fieldName = $resolveInfo->fieldName;

        return $source[$fieldName];
    }

    /**
     * Take an array of columns and return fields prepared for GraphQL object definition.
     *
     * @param array $columns
     * @param string $typeName
     * @param bool $includeHandles Whether columns also should be present by their field handles.
     * @return array
     */
    public static function prepareRowFieldDefinition(array $columns, string $typeName, $includeHandles = true): array
    {
        $contentFields = [];

        foreach ($columns as $columnKey => $columnDefinition) {
            switch ($columnDefinition['type']) {
                case 'date':
                case 'time':
                    $cellType = DateTime::getType();
                    break;
                case 'number':
                    $cellType = Number::getType();
                    break;
                case 'lightswitch':
                    $cellType = Type::boolean();
                    break;
                default:
                    $cellType = Type::string();
            }

            $contentFields[$columnKey] = $cellType;

            if ($includeHandles && !empty($columnDefinition['handle'])) {
                $contentFields[$columnDefinition['handle']] = $cellType;
            }
        }

        $contentFields = TypeManager::prepareFieldDefinitions($contentFields, $typeName);

        return $contentFields;
    }
}
