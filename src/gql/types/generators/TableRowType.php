<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use craft\fields\Table as TableField;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;
use craft\gql\types\Number;
use craft\gql\types\TableRow;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRowType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TableRowType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        /** @var TableField $context */
        $typeName = self::getName($context);

        $contentFields = [];

        foreach ($context->columns as $columnKey => $columnDefinition) {
            switch ($columnDefinition['type']){
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
            $contentFields[$columnDefinition['handle']] = $cellType;
        }

        $contentFields = TypeManager::prepareFieldDefinitions($contentFields, $typeName);

        // Generate a type for each entry type
        $tableRowType = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new TableRow([
            'name' => $typeName,
            'fields' => function() use ($contentFields) {
                return $contentFields;
            }
        ]));

        return [$tableRowType];
    }

    /**
     * @inheritdoc
     */
    public static function getName($context = null): string
    {
        /** @var TableField $context */
        return $context->handle . '_TableRow';
    }
}
