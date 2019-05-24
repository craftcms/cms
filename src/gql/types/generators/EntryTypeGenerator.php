<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\ElementType;
use craft\gql\types\EntryType;
use craft\models\EntryType as EntryTypeModel;

/**
 * Class EntryType
 */
class EntryTypeGenerator
{
    public static function generateTypes(): array
    {
        $entryTypes = Craft::$app->getSections()->getAllEntryTypes();

        $gqlTypes = [];

        foreach ($entryTypes as $entryType) {
            /** @var EntryTypeModel $entryType */
            $typeName = self::getName($entryType);
            $contentFields = $entryType->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $entryTypeFields = array_merge(EntryInterface::getFields(), $contentFieldGqlTypes);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new EntryType([
                'name' => $typeName,
                'fields' => function () use ($entryTypeFields) {
                    return $entryTypeFields;
                }
            ]));
        }

        return $gqlTypes;
    }

    /**
     * Return an entry type's GQL type name by entry type
     *
     * @param EntryTypeModel $entryType
     * @return string
     */
    public static function getName(EntryTypeModel $entryType): string
    {
        return $entryType->getSection()->handle . '_' . $entryType->handle . '_Entry';
    }
}
