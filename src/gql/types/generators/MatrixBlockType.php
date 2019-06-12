<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\MatrixBlock;
use craft\models\MatrixBlockType as MatrixBlockTypeModel;

/**
 * Class MatrixBlockTypeGenerator
 */
class MatrixBlockType implements BaseGenerator
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $matrixBlockTypes = Craft::$app->getMatrix()->getAllBlockTypes();
        $gqlTypes = [];

        foreach ($matrixBlockTypes as $matrixBlockType) {
            /** @var MatrixBlockTypeModel $matrixBlockType */
            $typeName = MatrixBlockElement::getGqlTypeNameByContext($matrixBlockType);
            $contentFields = $matrixBlockType->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $blockTypeFields = array_merge(MatrixBlockInterface::getFields(), $contentFieldGqlTypes);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new MatrixBlock([
                'name' => $typeName,
                'fields' => function () use ($blockTypeFields) {
                    return $blockTypeFields;
                }
            ]));
        }

        return $gqlTypes;
    }
}
