<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\TypeRegistry;
use craft\models\EntryType as EntryTypeModel;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;

/**
 * Class EntryType
 */
class EntryType
{
    public static function getTypes(): array
    {
        $entryTypes = Craft::$app->getSections()->getAllEntryTypes();
        $sections = Craft::$app->getSections()->getAllSections();
        $sectionHandleById = [];

        foreach ($sections as $section) {
            $sectionHandleById[$section->id] = $section->handle;
        }

        $gqlTypes = [];
        /** @var InterfaceType $interface */

        foreach ($entryTypes as $entryType) {
            /** @var EntryTypeModel $entryType */
            $typeName = $sectionHandleById[$entryType->sectionId] . '_' . $entryType->handle;
            $contentFields = $entryType->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $entryTypeFields = array_merge(EntryInterface::getFields(), $contentFieldGqlTypes);

            $gqlTypes[] = TypeRegistry::getType($typeName) ?: TypeRegistry::createType($typeName, new ObjectType([
                'name' => $typeName,
                'fields' => function () use ($entryTypeFields) {
                    return $entryTypeFields;
                },
                'interfaces' => [
                    EntryInterface::getType()
                ]
            ]));
        }

        return $gqlTypes;
    }
}
