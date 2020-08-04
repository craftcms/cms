<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use craft\gql\base\Mutation;
use GraphQL\Type\Definition\Type;

/**
 * Class Ping
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Ping extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        return [
            'ping' => [
                'type' => Type::string(),
                'resolve' => function() {
                    return 'A mutated pong';
                },
            ],
        ];
    }
}
