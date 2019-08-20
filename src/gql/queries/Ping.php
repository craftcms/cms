<?php
namespace craft\gql\queries;

use craft\gql\base\Query;
use GraphQL\Type\Definition\Type;

/**
 * Class Ping
 */
class Ping extends Query
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
