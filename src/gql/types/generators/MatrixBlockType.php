<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\fields\Matrix;
use craft\gql\base\GeneratorInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\elements\MatrixBlock;
use craft\models\MatrixBlockType as MatrixBlockTypeModel;

/**
 * Class MatrixBlockType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class MatrixBlockType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
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
            /** @var MatrixBlockTypeModel $matrixBlockType */
            $typeName = MatrixBlockElement::gqlTypeNameByContext($matrixBlockType);

            if (!($entity = GqlEntityRegistry::getEntity($typeName))) {
                $contentFields = $matrixBlockType->getFields();
                $contentFieldGqlTypes = [];

                /** @var Field $contentField */
                foreach ($contentFields as $contentField) {
                    $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
                }

                $blockTypeFields = array_merge(MatrixBlockInterface::getFieldDefinitions(), $contentFieldGqlTypes);

                // Generate a type for each entry type
                $entity = GqlEntityRegistry::createEntity($typeName, new MatrixBlock([
                    'name' => $typeName,
                    'fields' => function() use ($blockTypeFields) {
                        return $blockTypeFields;
                    }
                ]));
            }

            $gqlTypes[$typeName] = $entity;
        }

        return $gqlTypes;
    }
}
