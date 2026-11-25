<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use Craft;
use craft\gql\arguments\elements\ContentBlock as ContentBlockArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\types\generators\ContentBlock as ContentBlockGenerator;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class ContentBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return ContentBlockGenerator::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all content block elements.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        ContentBlockGenerator::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ContentBlockInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        $contentBlockArguments = ContentBlockArguments::getArguments();
        $allFieldArguments = ContentBlockArguments::getContentArguments();

        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), static::getDraftFieldDefinitions(), [
            'canonicalId' => [
                'name' => 'canonicalId',
                'type' => Type::int(),
                'description' => 'Returns the content block’s canonical ID.',
            ],
            'canonicalUid' => [
                'name' => 'canonicalUid',
                'type' => Type::string(),
                'description' => 'Returns the content block’s canonical UUID.',
            ],
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::int(),
                'description' => 'The ID of the field that contains the content block.',
            ],
            'fieldHandle' => [
                'name' => 'fieldHandle',
                'type' => Type::string(),
                'description' => 'The handle of the field that contains the content block.',
                'complexity' => Gql::singleQueryComplexity(),
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::int(),
                'description' => 'The ID of the content block’s owner element.',
            ],
            'localized' => [
                'name' => 'localized',
                'args' => [
                    ...$contentBlockArguments,
                    ...$allFieldArguments,
                ],
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The same element in other locales.',
                'complexity' => Gql::eagerLoadComplexity(),
            ],
        ]), self::getName());
    }
}
