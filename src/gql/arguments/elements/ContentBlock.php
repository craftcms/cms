<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\fields\ContentBlock as ContentBlockField;
use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class ContentBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'field' => [
                'name' => 'field',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the field the content blocks are contained by.',
            ],
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the field the content blocks are contained by, per the fields’ IDs.',
            ],
            'primaryOwnerId' => [
                'name' => 'primaryOwnerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the primary owner element of the content blocks, per the owners’ IDs.',
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the owner element of the content blocks, per the owners’ IDs.',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $gqlService = Craft::$app->getGql();
        return $gqlService->getOrSetContentArguments(ContentBlockElement::class, function() use ($gqlService): array {
            /** @var ContentBlockField[] $fields */
            $fields = Craft::$app->getFields()->getFieldsByType(ContentBlockField::class);

            $arguments = [];
            foreach ($fields as $field) {
                $arguments += $gqlService->getFieldLayoutArguments($field->getFieldLayout());
            }
            return $arguments;
        });
    }
}
