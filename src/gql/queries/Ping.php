<?php
namespace craft\gql\queries;

use GraphQL\Type\Definition\Type;

/**
 * Class Ping
 */
class Ping
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        return [
            'ping' => [
                'type' => Type::string(),
                'resolve' => function () { return 'pong';},
            ],
        ];
    }
}
