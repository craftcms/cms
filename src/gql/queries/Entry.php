<?php
namespace craft\gql\queries;

use Craft;
use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use GraphQL\Type\Definition\Type;

/**
 * Class Section
 */
class Entry
{
    /**
     * @inheritdoc
     */
    public static function getQueries(): array
    {
        // inheritance. base element query shares all that jazz.
        return [
            'queryEntries' => [
                'type' => Type::listOf(EntryInterface::getType()),
                'args' => [
                    'type' => Type::string(),
                ],
                'resolve' => function ($rootValue, $args) {
                    if (isset($args['type'])) {
                        return EntryElement::find()->type($args['type'])->all();
                    }
                },
            ],
        ];
    }
}