<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use craft\gql\base\Query;
use GraphQL\Type\Definition\Type;

/**
 * Class Ping
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Ping extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries(bool $checkToken = true): array
    {
        return [
            'ping' => [
                'type' => Type::string(),
                'resolve' => function() {
                    return 'pong';
                },
            ],
        ];
    }
}
