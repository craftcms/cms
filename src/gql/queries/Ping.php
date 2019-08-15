<?php
namespace craft\gql\queries;

use GraphQL\Type\Definition\Type;

/**
 * Class Ping
 */
class Ping extends BaseQuery
{
    /**
     * @inheritdoc
     */
    public static function getQueries($checkToken = true): array
    {
        return [
            'ping' => [
                'type' => Type::string(),
                'resolve' => function () { return 'pong';},
            ],
        ];
    }
}
