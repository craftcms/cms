<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\Tag as TagElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\TypeManager;
use craft\gql\types\elements\Tag;
use craft\helpers\Gql as GqlHelper;
use craft\models\TagGroup;

/**
 * Class TagType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TagType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $tagGroups = Craft::$app->getTags()->getAllTagGroups();
        $gqlTypes = [];

        foreach ($tagGroups as $tagGroup) {
            /** @var TagGroup $tagGroup */
            $typeName = TagElement::gqlTypeNameByContext($tagGroup);
            $requiredContexts = TagElement::gqlScopesByContext($tagGroup);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $tagGroup->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $tagGroupFields = TypeManager::prepareFieldDefinitions(array_merge(TagInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Tag([
                'name' => $typeName,
                'fields' => function() use ($tagGroupFields) {
                    return $tagGroupFields;
                }
            ]));
        }

        return $gqlTypes;
    }
}
