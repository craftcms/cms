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
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\TypeManager;
use craft\gql\types\elements\Tag;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\TagGroup;

/**
 * Class TagType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TagType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $tagGroups = Craft::$app->getTags()->getAllTagGroups();
        $gqlTypes = [];

        foreach ($tagGroups as $tagGroup) {
            $requiredContexts = TagElement::gqlScopesByContext($tagGroup);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            // Generate a type for each tag group
            $type = static::generateType($tagGroup);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType($context): ObjectType
    {
        /** @var TagGroup $tagGroup */
        $typeName = TagElement::gqlTypeNameByContext($context);
        $contentFields = $context->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
        }

        $tagGroupFields = TypeManager::prepareFieldDefinitions(array_merge(TagInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Tag([
            'name' => $typeName,
            'fields' => function() use ($tagGroupFields) {
                return $tagGroupFields;
            }
        ]));
    }
}
