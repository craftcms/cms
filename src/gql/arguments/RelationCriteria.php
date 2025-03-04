<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

/**
 * Class RelationCriteria
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class RelationCriteria extends Arguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return [
            'relatedViaField' => [
                'name' => 'relatedViaField',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the relations based on the field they were created in.',
            ],
            'relatedViaSite' => [
                'name' => 'relatedViaSite',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the relations based on the site they were created in.',
            ],
        ];
    }
}
