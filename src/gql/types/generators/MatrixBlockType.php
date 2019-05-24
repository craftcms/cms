<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;
use craft\helpers\StringHelper;
use craft\models\MatrixBlockType as MatrixBlockTypeModel;
use craft\models\Section;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class MatrixBlockType
 */
class MatrixBlockType
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
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
                'name' => $typeName,
                'fields' => function () use ($blockTypeFields) {
                    return $blockTypeFields;
                },
                'interfaces' => [
                    MatrixBlockInterface::getType()
                ],
                // This resolver is responsible for resolving any field on the Matrix Block
                'resolveField' => function (MatrixBlockElement $blockType, $arguments, $context, ResolveInfo $resolveInfo) {
                    $fieldName = $resolveInfo->fieldName;

                    if (StringHelper::substr($fieldName, 0, 5) === 'field') {
                        $field = $blockType->getField();
                        $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 5));

                        $value = $field->$property ?? null;
                    } else if (StringHelper::substr($fieldName, 0, 9) === 'ownerSite') {
                        $owner = $blockType->getSite();
                        $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 9));

                        $value = $owner->$property ?? null;
                    } else if (StringHelper::substr($fieldName, 0, 5) === 'owner') {
                        $owner = $blockType->getOwner();
                        $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 5));

                        $value = $owner->$property ?? null;
                    } else if (StringHelper::substr($fieldName, 0, 4) === 'type') {
                        $entryType = $blockType->getType();
                        $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 4));

                        $value = $entryType->$property ?? null;
                    } else {
                        $value = $blockType->$fieldName;
                    }

                    return Gql::applyDirectivesToField($value, $resolveInfo);

                },
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
