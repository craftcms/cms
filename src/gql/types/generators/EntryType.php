<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\StringHelper;
use craft\models\EntryType as EntryTypeModel;
use craft\models\Section;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class EntryType
 */
class EntryType
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
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
                'name' => $typeName,
                'fields' => function () use ($entryTypeFields) {
                    return $entryTypeFields;
                },
                'interfaces' => [
                    EntryInterface::getType()
                ],
                // This resolver is responsible for resolving any field on the Entry
                'resolveField' => function (EntryElement $entry, $arguments, $context, ResolveInfo $resolveInfo) {
                    $fieldName = $resolveInfo->fieldName;

                    if (StringHelper::substr($fieldName, 0, 7) === 'section') {
                        $section = $entry->getSection();
                        $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 7));

                        return $section->$property ?? null;
                    }

                    if (StringHelper::substr($fieldName, 0, 4) === 'type') {
                        $entryType = $entry->getType();
                        $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 4));

                        return $entryType->$property ?? null;
                    }

                    return $entry->$fieldName;
                },
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
    public static function getName(EntryTypeModel $entryType)
    {
        return $entryType->getSection()->handle . '_' . $entryType->handle . '_Entry';
    }
}
