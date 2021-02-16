<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\elements\db\AssetQuery;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\db\TagQuery;
use craft\elements\db\UserQuery;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\gql\ArgumentManager;
use craft\gql\base\ArgumentHandler;
use craft\gql\base\ArgumentHandlerInterface;
use craft\gql\base\RelationArgumentHandler;
use craft\gql\handlers\RelatedAssets;
use craft\gql\handlers\RelatedCategories;
use craft\gql\handlers\RelatedEntries;
use craft\gql\handlers\RelatedTags;
use craft\gql\handlers\RelatedUsers;
use craft\gql\TypeManager;
use craft\models\GqlSchema;
use craft\services\Gql;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\base\Event;

class ArgumentHandlerTest extends Unit
{
    /**
     * Test whether it's possible to modify fields
     *
     * @dataProvider integrationTestDataProvider
     *
     * @param string $argumentString
     * @param array $expectedResult
     * @throws \Exception
     */
    public function testArgumentHandlerIntegration(string $argumentString, array $expectedResult)
    {
        $gql = Craft::$app->getGql();
        $gql->flushCaches();
        Craft::$app->getConfig()->getGeneral()->enableGraphQlCaching = false;

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $event->queries['integrationQuery'] = [
                'type' => Type::string(),
                'args' => [
                    'initial' => Type::int(),
                    'multiplier' => Type::int(),
                    'result' => Type::int(),
                    'wipeInitial' => Type::boolean(),
                ],
                'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) {
                    if (!empty($context['argumentManager'])) {
                        $arguments = $context['argumentManager']->prepareArguments($arguments);
                    }

                    ksort($arguments);
                    // Encode as string, so we can pass a data structure as a string.
                    return json_encode($arguments);
                }
            ];
        });

        $handler = $this->make(RelatedEntries::class, [
            'handleArgumentCollection' => function(array $argumentList = []): array {
                $argumentList['result'] = $argumentList['initial'] * $argumentList['multiplier'];

                if (!empty($argumentList['wipeInitial'])) {
                    unset($argumentList['initial']);
                }

                return $argumentList;
            }
        ]);

        Event::on(ArgumentManager::class, ArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, function(RegisterGqlArgumentHandlersEvent $event) use ($handler) {
            $event->handlers['initial'] = $handler;
        });

        $result = $gql->executeQuery(new GqlSchema(), "{integrationQuery ($argumentString)}");
        $this->assertEquals($expectedResult, json_decode($result['data']['integrationQuery'], true));
    }

    /**
     * Test whether relation argument handlers return the right element query and format the `relatedTo` argument correctly.
     *
     * @dataProvider relationArgumentHandlerProvider
     *
     * @param array $handlers
     * @param $arguments
     * @param $expectedRelatedTo
     * @throws \yii\base\InvalidConfigException
     */
    public function testRelationArgumentHandlers(array $handlers, $arguments, $expectedRelatedTo): void
    {
        $argumentManager = new ArgumentManager();

        foreach ($handlers as $argumentName => $handler) {
            /** @var ArgumentHandlerInterface $handler */
            $handler->setArgumentManager($argumentManager);
            $argumentManager->setHandler($argumentName, $handler);
        }

        $arguments = $argumentManager->prepareArguments($arguments);

        $this->assertSame($expectedRelatedTo, $arguments['relatedTo']);
    }

    public function relationArgumentHandlerProvider(): array
    {
        $getIds = function(ElementQueryInterface $elementQuery, array $criteria = []) {
            $this->assertInstanceOf($criteria['expected'], $elementQuery);
            return $criteria['return'];
        };

        $handlers = [
            'relatedToAssets' => $this->make(RelatedAssets::class, ['getIds' => $getIds]),
            'relatedToEntries' => $this->make(RelatedEntries::class, ['getIds' => $getIds]),
            'relatedToCategories' => $this->make(RelatedCategories::class, ['getIds' => $getIds]),
            'relatedToTags' => $this->make(RelatedTags::class, ['getIds' => $getIds]),
            'relatedToUsers' => $this->make(RelatedUsers::class, ['getIds' => $getIds]),
        ];

        return [
            [[], ['relatedToAll' => [1, 2, 3]], 'relatedTo' => ['and', ['element' => 1], ['element' => 2], ['element' => 3]]],
            [$handlers, ['relatedToAssets' => ['expected' => AssetQuery::class, 'return' => [[1, 2]]]], ['and', ['element' => [1, 2]]]],
            [$handlers, ['relatedToEntries' => ['expected' => EntryQuery::class, 'return' => [[3], [4]]]], ['and', ['element' => [3]], ['element' => [4]]]],
            [$handlers, ['relatedToCategories' => ['expected' => CategoryQuery::class, 'return' => []]], ['and', ['element' => [0]]]],
            [$handlers, ['relatedToTags' => ['expected' => TagQuery::class, 'return' => [[7], [8]]]], ['and', ['element' => [7]], ['element' => [8]]]],
            [$handlers, ['relatedToUsers' => ['expected' => UserQuery::class, 'return' => [[9, 10]]]], ['and', ['element' => [9, 10]]]],

            [
                $handlers,
                [
                    'relatedToEntries' => ['expected' => EntryQuery::class, 'return' => [[3, 4]]],
                    'relatedToAssets' => ['expected' => AssetQuery::class, 'return' => [[9,10]]]
                ],
                [
                    'and',
                    ['element' => [3, 4]],
                    ['element' => [9,10]]
                ],
            ],
            [
                $handlers,
                [
                    'relatedToEntries' => ['expected' => EntryQuery::class, 'return' => [[3], [4]]],
                    'relatedTo' => [8, 9]
                ],
                [
                    'and',
                    ['element' => [8, 9]],
                    ['element' => [3]],
                    ['element' => [4]],
                ],
            ],
            [
                $handlers,
                [
                    'relatedToEntries' => ['expected' => EntryQuery::class, 'return' => [[3, 4]]],
                    'relatedTo' => ['and', 8, 9]
                ],
                [
                    'and',
                    ['element' => 8],
                    ['element' => 9],
                    ['element' => [3, 4]],
                ],
            ],
        ];
    }

    public function integrationTestDataProvider(): array
    {
        return [
            ['initial: 5 multiplier: 2', ['initial' => 5, 'multiplier' => 2, 'result' => 10]],
            ['initial: 3 multiplier: -2', ['initial' => 3, 'multiplier' => -2, 'result' => -6]],
            ['initial: 3 multiplier: -2 wipeInitial: true', ['multiplier' => -2, 'result' => -6, 'wipeInitial' => true]],
        ];
    }
}
