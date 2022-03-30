<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\Tag as TagElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\types\elements\Tag;
use craft\helpers\Gql as GqlHelper;

/**
 * Class TagType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class TagType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
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
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = TagElement::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);
        $tagGroupFields = array_merge(TagInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Tag([
            'name' => $typeName,
            'fields' => function() use ($tagGroupFields, $typeName) {
                return Craft::$app->getGql()->prepareFieldDefinitions($tagGroupFields, $typeName);
            },
        ]));
    }
}
