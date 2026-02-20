<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input;

use craft\base\Field;
use craft\fields\ContentBlock as ContentBlockField;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class ContentBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends InputObjectType
{
    /**
     * Create the type for a Matrix field.
     *
     * @param ContentBlockField $context
     * @return mixed
     */
    public static function getType(ContentBlockField $context): mixed
    {
        $typeName = $context->handle . '_ContentBlockInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines the content within the “%s” Content Block field’s data.', $context->name),
            'fields' => function() use ($context) {
                $fields = [];

                // Get the field input types
                foreach ($context->getFieldLayout()->getCustomFields() as $field) {
                    /** @var Field $field */
                    $fields[$field->handle] = $field->getContentGqlMutationArgumentType();
                }

                return $fields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    /**
     * Normalize Content Block GraphQL input data to what Craft expects.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function normalizeValue(mixed $value): mixed
    {
        return $value ? ['fields' => $value] : [];
    }
}
