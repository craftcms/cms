<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use craft\fields\Table as TableField;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\TableRow;

/**
 * Class TableRowType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TableRowType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        return [static::generateType($context)];
    }

    /**
     * @inheritdoc
     */
    public static function getName($context = null): string
    {
        /** @var TableField $context */
        return $context->handle . '_TableRow';
    }

    /**
     * @inheritdoc
     */
    public static function generateType($context): ObjectType
    {
        /** @var TableField $context */
        $typeName = self::getName($context);
        $contentFields = TableRow::prepareRowFieldDefinition($context->columns, $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new TableRow([
            'name' => $typeName,
            'fields' => function() use ($contentFields) {
                return $contentFields;
            }
        ]));
    }
}
