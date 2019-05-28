<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\Entry;
use craft\models\EntryType as EntryTypeModel;

/**
 * Class EntryTypeGenerator
 */
class EntryType implements BaseGenerator
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
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
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Entry([
                'name' => $typeName,
                'fields' => function () use ($entryTypeFields) {
                    return $entryTypeFields;
                }
            ]));
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function getName($context = null): string
    {
        /** @var EntryTypeModel $context */
        return $context->getSection()->handle . '_' . $context->handle . '_Entry';
    }
}
