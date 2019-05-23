<?php
namespace craft\gql\queries;

use craft\gql\arguments\elements\MatrixBlock as MatrixBlockArguments;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\resolvers\elements\MatrixBlock as MatrixBlockResolver;
use GraphQL\Type\Definition\Type;

/**
 * Class Section
 */
class MatrixBlock
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        // inheritance. base element query shares all that jazz.
        return [
            'queryMatrixBlocks' => [
                'type' => Type::listOf(MatrixBlockInterface::getType()),
                'args' => MatrixBlockArguments::getArguments(),
                'resolve' => MatrixBlockResolver::class . '::resolve',
            ],
        ];
    }
}