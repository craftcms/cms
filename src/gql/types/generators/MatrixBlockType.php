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

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new MatrixBlock([
            'name' => $typeName,
            'fields' => function() use ($context, $typeName) {
                $contentFieldGqlTypes = self::getContentFields($context);
                $blockTypeFields = array_merge(MatrixBlockInterface::getFieldDefinitions(), $contentFieldGqlTypes);
                return Craft::$app->getGql()->prepareFieldDefinitions($blockTypeFields, $typeName);
            },
        ]));
    }
}
