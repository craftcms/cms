<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\Entry as EntryElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\TypeManager;
use craft\gql\types\elements\Entry;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType as EntryTypeModel;

/**
 * Class EntryType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class EntryType implements GeneratorInterface
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
            $typeName = EntryElement::gqlTypeNameByContext($entryType);
            $requiredContexts = EntryElement::gqlScopesByContext($entryType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $entryType->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $entryTypeFields = TypeManager::prepareFieldDefinitions(array_merge(EntryInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Entry([
                'name' => $typeName,
                'fields' => function() use ($entryTypeFields) {
                    return $entryTypeFields;
                }
            ]));
        }

        return $gqlTypes;
    }
}
