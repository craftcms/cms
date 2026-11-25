<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\fields\ContentBlock as ContentBlockField;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\ContentBlock as ContentBlockInterface;
use craft\gql\types\elements\ContentBlock as ContentBlockType;

/**
 * Class ContentBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        if ($context instanceof ContentBlockField) {
            $fields = [$context];
        } else {
            $fields = Craft::$app->getFields()->getFieldsByType(ContentBlockField::class);
        }

        $gqlTypes = [];

        foreach ($fields as $field) {
            $type = static::generateType($field);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        /** @var ContentBlockField $context */
        $typeName = ContentBlockElement::gqlTypeName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new ContentBlockType([
            'name' => $typeName,
            'fields' => function() use ($context, $typeName) {
                $contentFieldGqlTypes = self::getContentFields($context->getFieldLayout());
                $contentBlockFields = array_merge(ContentBlockInterface::getFieldDefinitions(), $contentFieldGqlTypes);
                return Craft::$app->getGql()->prepareFieldDefinitions($contentBlockFields, $typeName);
            },
        ]));
    }
}
