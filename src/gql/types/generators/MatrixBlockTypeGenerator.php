<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\MatrixBlockType;
use craft\models\MatrixBlockType as MatrixBlockTypeModel;
use craft\models\Section;

/**
 * Class MatrixBlockType
 */
class MatrixBlockTypeGenerator
{
    public static function generateTypes(): array
    {
        $matrixBlockTypes = Craft::$app->getMatrix()->getAllBlockTypes();

        $gqlTypes = [];

        foreach ($matrixBlockTypes as $matrixBlockType) {
            /** @var MatrixBlockTypeModel $matrixBlockType */
            $typeName = self::getName($matrixBlockType);
            $contentFields = $matrixBlockType->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $blockTypeFields = array_merge(MatrixBlockInterface::getFields(), $contentFieldGqlTypes);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new MatrixBlockType([
                'name' => $typeName,
                'fields' => function () use ($blockTypeFields) {
                    return $blockTypeFields;
                }
            ]));
        }

        return $gqlTypes;
    }

    /**
     * Return a block type's GQL type name by block type
     *
     * @param Section $section
     * @param MatrixBlockTypeModel $matrixBlockType
     * @return string
     */
    public static function getName(MatrixBlockTypeModel $matrixBlockType)
    {
        return $matrixBlockType->getField()->handle . '_' . $matrixBlockType->handle . '_BlockType';
    }
}
