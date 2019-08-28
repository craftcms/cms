<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use craft\gql\base\StructureElementArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends StructureElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'editable' => [
                'name' => 'editable',
                'type' => Type::boolean(),
                'description' => 'Whether to only return entries that the user has permission to edit.'
            ],
            'section' => [
                'name' => 'section',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the section handles the entries belong to.'
            ],
            'sectionId' => [
                'name' => 'sectionId',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on the sections the entries belong to, per the sections’ IDs.'
            ],
            'type' => [
                'name' => 'type',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the entries’ entry type handles.'
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on the entries’ entry types, per the types’ IDs.'
            ],
            'authorId' => [
                'name' => 'authorId',
                'type' => Type::listOf(Type::boolean()),
                'description' => 'Narrows the query results based on the entries’ authors.'
            ],
            'authorGroup' => [
                'name' => 'authorGroup',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the user group the entries’ authors belong to.'
            ],
            'postDate' => [
                'name' => 'postDate',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the entries’ post dates.'
            ],
            'before' => [
                'name' => 'before',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only entries that were posted before a certain date.'
            ],
            'after' => [
                'name' => 'after',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only entries that were posted on or after a certain date.'
            ],
            'expiryDate' => [
                'name' => 'expiryDate',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the entries’ expiry dates.'
            ],
        ]);
    }
}
