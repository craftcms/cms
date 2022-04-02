<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\fields\Matrix;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\types\elements\MatrixBlock;

/**
 * Class MatrixBlockType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class MatrixBlockType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        // If we need matrix block types for a specific Matrix field, fetch those.
        if ($context) {
            /** @var Matrix $context */
            $matrixBlockTypes = $context->getBlockTypes();
        } else {
            $matrixBlockTypes = Craft::$app->getMatrix()->getAllBlockTypes();
        }

        $gqlTypes = [];

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $type = static::generateType($matrixBlockType);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = MatrixBlockElement::gqlTypeNameByContext($context);

        if (!($entity = GqlEntityRegistry::getEntity($typeName))) {
            $contentFieldGqlTypes = self::getContentFields($context);
            $blockTypeFields = array_merge(MatrixBlockInterface::getFieldDefinitions(), $contentFieldGqlTypes);

            // Generate a type for each block type
            $entity = GqlEntityRegistry::getEntity($typeName);

            if (!$entity) {
                $entity = new MatrixBlock([
                    'name' => $typeName,
                    'fields' => function() use ($blockTypeFields, $typeName) {
                        return Craft::$app->getGql()->prepareFieldDefinitions($blockTypeFields, $typeName);
                    },
                ]);

                // It's possible that creating the matrix block triggered creating all matrix block types, so check again.
                $entity = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, $entity);
            }
        }

        return $entity;
    }
}
